<?php

declare(strict_types=1);

namespace Kantine\Admin;
use Shared\Entity\Costumer;
use DateTime;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\Form\Type\DateRangePickerType;
use Sonata\Form\Type\DateTimePickerType;
use Symfony\Component\Form\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderAdmin extends AbstractAdmin
{
    public function __construct(private TranslatorInterface $translator) {}
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        // kantine shouldnt have access to Costumerdata, use ID instead
        $costumerAdmin = $this->getConfigurationPool()->getAdminByClass(Costumer::class);
        if($costumerAdmin->hasAccess('list')){
            $filter
            ->add('Costumer', ModelFilter::class,
            [
                'field_type' => ModelAutocompleteType::class,
                'field_options' => [
                    'property' => ['name'],
                    'minimum_input_length' => 1,
                    'to_string_callback' => fn($user, $property)=> $user->getFullNameWithId()
                ]
            ])
            ->add('Costumer.active', null, [
                'label' => 'Costumer active'
            ]);
        } else {
            $filter
            ->add('Costumer');
        }
        $filter
            ->add('order_dateTime', DateRangeFilter::class, [
                    'field_type'=> DateRangePickerType::class,
                    'field_options' => [
                    'field_options' => [
                        'format' => 'dd.MM.yyyy'
                     ],
                ]])
            ->add('ordered_item')
            ->add('tax')
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $costumerAdmin = $this->getConfigurationPool()->getAdminByClass(Costumer::class);
        if($costumerAdmin->hasAccess('list')){
            $list
            ->add('Costumer',null, [
                'class' => Costumer::class,
                'label' => 'Costumer',
                'associated_property' => 'fullNameWithId',
            ]);
        } else {
            $list
            ->add('Costumer', EntityType::class, [
                'class' => Costumer::class,
            ]);
        }
        $list
            ->add('order_dateTime')
            ->add('orderFormatted')
            ->add('tax')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    protected function configureExportFields(): array
    {
        return ['Costumer.id', 'order_dateTime', 'OrderNum', 'tax'];
    }

    protected function configureFormFields(FormMapper $form): void
    {
        // kantine shouldnt have access to Customerdata, use ID instead
        $costumerAdmin = $this->getConfigurationPool()->getAdminByClass(Costumer::class);
        if($costumerAdmin->hasAccess('list')){
            $form->add('Costumer', ModelAutocompleteType::class, [
                'class' => Costumer::class,
                // 'choice_label' => 'id',
                'minimum_input_length' => 1,
                'property' => ['name', 'id'],
                'to_string_callback' => fn($user, $property)=> $user->getFullNameWithId()
            ]);
        } else {
            $form->add('Costumer');
        }
        $form
            ->add('ordered_item', MoneyType::class, [])
            ->add('tax', HiddenType::class, ['data' => 7])
        ;

        if($this->isGranted("ROLE_ADMIN")){
            $form->add('order_dateTime', DateTimePickerType::class);
        }
    }

    /**
     * @phpstan-return OrderAdmin
     */
    protected function createNewInstance(): object
    {
        $instance = parent::createNewInstance();
        $instance->setOrderDateTime(new DateTime());

        return $instance;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('Costumer', EntityType::class, [
                'class' => Costumer::class,
                'choice_label' => 'id',
            ])
            ->add('order_dateTime')
            ->add('ordered_item', FieldDescriptionInterface::TYPE_CURRENCY, ['currency' => 'EUR', 'data_transformer' => new MoneyToLocalizedStringTransformer(locale:'deDE'),])
            ->add('tax')
        ;
    }
}
