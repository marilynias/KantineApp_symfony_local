<?php

namespace Zeiterfassung\Admin;

use Shared\Entity\Costumer;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;

use Sonata\Form\Type\DatePickerType;
use Sonata\Form\Type\DateTimePickerType;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\Form\Type\DateRangePickerType;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TimeEntryAdmin extends AbstractAdmin
{
    public function __construct(private UsageTrackingTokenStorage $ts, private TranslatorInterface $translator){}

    // DEPRICATED
    // protected $baseRouteName = 'admin_time_entry';
    // protected $baseRoutePattern = 'attendance';
    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'admin_time_entry';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'attendance';
    }

    // -------------------------------------------------------------------
    // Helper to avoid duplicate user join in filters
    // -------------------------------------------------------------------

    private function costumerToStr(Costumer $user): string
    {
        if (!$user instanceof Costumer) {
            return (string)$user;
        }
        $dept = $user->getDepartment()=="" ? $this->translator->trans('No Dept'):$user->getDepartment();
        return sprintf('[%s] %s', $dept, $user->getFullName());
    }

    private function ensureUserJoin(QueryBuilder $qb, string $alias): void
    {
        $joins = $qb->getDQLPart('join');
        if (isset($joins[$alias])) {
            foreach ($joins[$alias] as $join) {
                if ($join->getAlias() === 'u') {
                    return;
                }
            }
        }
        $qb->leftJoin("$alias.user", "u");
    }

    // -------------------------------------------------------------------
    // BATCH ACTIONS
    // -------------------------------------------------------------------

    protected function configureBatchActions(array $actions): array
    {
        if (isset($actions['delete'])) {
            unset($actions['delete']);
        }

        $actions['export as report'] = [
            'ask_confirmation' => false,
            'controller' => 'Zeiterfassung\Controller\TimeEntryBatchController::batchGetReportAction',
        ];


        return $actions;
    }

    // -------------------------------------------------------------------
    // TEMPLATES
    // -------------------------------------------------------------------

    protected function configureTemplates(): array
    {
        return [
            'list' => 'admin/_auto_refresh_list.html.twig',
        ];
    }

    // -------------------------------------------------------------------
    // FORM
    // -------------------------------------------------------------------

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user', ModelAutocompleteType::class, [
                'label' => 'User',
                'btn_add' => false,
                'required' => true,
                'placeholder' => 'Select user',
                'property' => ['firstname', 'lastname'],
                'minimum_input_length' => 1,
                'to_string_callback' => fn($user, $property) =>  $this->costumerToStr($user),
                'constraints' => [
                    new NotNull(null, 'Please select a user.'),
                ],
            ])
            ->add('checkinTime', DateTimePickerType::class, [
                'label' => 'Check-in',
                'widget' => 'single_text',
                'html5' => false,
                'help' => '(Format: dd.mm.yyyy hh:mm)',
                'format' => 'dd.MM.yyyy HH:mm',
                'required' => true,
                'datepicker_options' => [
                    'allowInputToggle' => true,

                ],
            ])
            ->add('checkoutTime', DateTimePickerType::class, [
                'label' => 'Check-out',
                'widget' => 'single_text',
                'html5' => false,
                'help' => '(Format: dd.mm.yyyy hh:mm)',
                'format' => 'dd.MM.yyyy HH:mm',
                'required' => false,
                'datepicker_options' => [
                    'allowInputToggle' => true,

                ],
            ]);
    }

    // -------------------------------------------------------------------
    // FILTERS
    // -------------------------------------------------------------------
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        // user
        $filter->add(
            'user',
            ModelFilter::class,
            [
                'field_type' => ModelAutocompleteType::class,
                'field_options' => [
                    'property' => ['firstname', 'lastname'],
                    'minimum_input_length' => 1,
                    'to_string_callback' => function ($user, $property) {
                        return $this->costumerToStr($user);
                    },
                ]
            ]
        );

        $filter->add('user.Department', ChoiceFilter::class, [
            'field_type' => ChoiceType::class,
            'field_options' => [
                'choices' => Costumer::DEPARTMENTS
            ]
        ]);

        $filter->add('missingCheckinCheckout', CallbackFilter::class, [
            'field_type' => CheckboxType::class,
            'callback' => function ($qb, $alias, $field, $value) {
                if (!$value || !$value->hasValue() || $value->getValue() !== true) return false;
                $qb->andWhere("$alias.checkinTime IS NULL OR $alias.checkoutTime IS NULL");
                return true;
            },
        ]);

        $filter->add('today', CallbackFilter::class, [
            'field_type' => CheckboxType::class,
            'callback' => function ($qb, $alias, $field, $value) {
                if (!$value || !$value->hasValue() || $value->getValue() !== true) return false;
                $todayStart = new \DateTime('today');
                $todayEnd   = new \DateTime('tomorrow');
                $qb->andWhere("$alias.checkinTime BETWEEN :ts AND :te")
                    ->setParameter('ts', $todayStart)
                    ->setParameter('te', $todayEnd);
                return true;
            },
        ]);

        $filter->add('checkinTime', DateRangeFilter::class, [
            'field_type' => DateRangePickerType::class,
            'label' => $this->translator->trans('From - to'),
        ]);
    }

    // -------------------------------------------------------------------
    // LIST VIEW
    // -------------------------------------------------------------------
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('user', null, [
                'label' => 'Name',
                'associated_property' => 'fullName',
                'sort_field_mapping' => [
                    'fieldName' => 'lastname',
                ],
            ])
            ->add('user.department', null, ['label' => 'Department'])
            ->addIdentifier('checkinTime', null, ['label' => 'Check-in', 'format' => 'd.m.Y - H:i:s'])
            ->add('checkoutTime', null, ['label' => 'Check-out', 'format' => 'd.m.Y - H:i:s'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Actions',
                'actions' => ['edit' => [], 'delete' => []],
            ]);
    }

    // -------------------------------------------------------------------
    // SHOW VIEW
    // -------------------------------------------------------------------
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('user.fullName', null, ['label' => 'Name'])
            ->add('user.department', null, ['label' => 'Department'])
            ->add('checkinTime', null, [
                'label' => 'Check-in',
                'format' => 'd.m.Y - H:i',
                ])
            ->add('checkoutTime', null, [
                'label' => 'Check-out',
                'format' => 'd.m.Y - H:i',
                ]);
    }

    // -------------------------------------------------------------------
    // EXPORT FIELDS
    // -------------------------------------------------------------------
    protected function configureExportFields(): array
    {
        return ['user.fullName', 'user.Department', 'checkinTime', 'checkoutTime'];
    }

//     public function getExportFormats(): array
// {
//     return ['xlsx', 'pdf'];
// }

    // -------------------------------------------------------------------
    // DEFAULT SORTING + TODAY FILTER
    // -------------------------------------------------------------------

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    
}
