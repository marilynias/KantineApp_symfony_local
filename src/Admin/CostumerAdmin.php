<?php

declare(strict_types=1);

namespace Shared\Admin;

use Doctrine\DBAL\Schema\Exception\NotImplemented;
use Shared\Entity\Costumer;
use Shared\Entity\Tags;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Form\ChoiceList\ModelChoiceLoader;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;
use Sonata\Form\Type\CollectionType;
use Sonata\Form\Type\DatePickerType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\NotNull;


// function getTags(AdminInterface $admin, string $property, $value): void {
//     // throw new NotImplemented("not implemented");
//     $datagrid = $admin->getDatagrid();
//     $query = $datagrid->getQuery();
//     $query
//         ->andWhere($query->getRootAlias() . 'id=:barValue')
//         ->setParameter('barValue', $admin->getRequest()->get('bar'))
//     ;
//     $datagrid->setValue($property, null, $value);
// }

final class CostumerAdmin extends AbstractAdmin
{
    private function getTags(AdminInterface $admin, string $property, $value)
    {
        throw new NotImplemented("not implemented");
        $datagrid = $admin->getDatagrid();
        $query = $datagrid->getQuery();
        $query
            ->andWhere($query->getRootAlias() . '.foo=:barValue')
            ->setParameter('barValue', $admin->getRequest()->get('bar'))
        ;
        $datagrid->setValue($property, null, $value);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('firstname')
            ->add('lastname')
            ->add('active', null, [
                'editable' => true,
                'inverse'  => true,
            ])
            ->add('enddate')
            ->add('Department', ChoiceFilter::class, [
            'field_type' => ChoiceType::class,
            'choices_as_values' => true,
            'field_options' => [
                'choices' => Costumer::DEPARTMENTS,
                'choice_label'=>function (mixed $value): TranslatableMessage|string|null {
                    return $value;
                },
                // "expanded" => true,
                "multiple" => true,
            ]
        ]);
            // ->add('Barcode', FieldDescriptionInterface::TYPE_HTML/*, ["required" => false, ['help' => '<img src="' . $this->getSubject()->getBarcode() . '" />']]*/)
        ;
    }

    protected function configureListFields(ListMapper $list): void
    {
        // $q = getTags($this, 'id', 1);
        $test = $this->getModelManager();
        $q = $this->getModelManager()->getEntityManager(Tags::class)->createQueryBuilder('t')
            ->select('t.name')
            ->from('Shared\Entity\Tags', 't')
            // ->setMaxResults(10)
            ->where("t.id > 0")
            ->getQuery();
        // $q->execute();
        
        
        // ModelManagerInterface;

        $list
            ->add('id', null, ['read', ])
            ->add('firstname')
            ->add('lastname')
            ->add('active', null, [
                'editable' => true
            ])
            ->add('Department', 
            // 'tags_lst',
            // ChoiceType::class,
            FieldDescriptionInterface::TYPE_CHOICE, 
            [
                'choices' => Costumer::DEPARTMENTS,
                // 'key_translation_domain' => true,
                // 'value_translation_domain' => true,
                // 'choice_translation_domain' => true, 
                // 'dataTransformer' => 'ModelsToArrayTransformer',
                'choice_translation_domain' => 'messages', 
                'multiple' => true,
                'editable' => true,
            ])
            // ->add('tags', FieldDescriptionInterface::TYPE_MANY_TO_MANY, [
            ->add('tags', FieldDescriptionInterface::TYPE_CHOICE, [
                'btn_add' => true,
                // 'label' => 'Tags',
                // 'class' => Tags::class,
                'multiple' => true,
                'editable' => true,
                // 'display' => 'both',
                // 'class' => Tags::class,
                // 'query' => $q
                'choices' => $q->getResult(),
                // 'data-url' => '/',
                // 'identifier'=>false,
                // 'code' => function(AdminInterface $admin, string $property, $value){
                //     throw new NotImplemented("not implemented");
                //     getTags($admin, $property, $value);
                // },
            
                // 'associated_property' => 'name',
                // ''
            ])
            ->add('enddate', null, [
                'widget' => 'single_text',
                'html5' => false,
                'help' => '(Format: dd.mm.yyyy)',
                'format' => 'd.m.Y'])
            ->add('Barcode', 'barcode')                         // custom types defined in config/packages/sonata_doctrine_orm_admin.yaml
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
    }

    protected function configureExportFields(): array
    {
        return ['id', 'firstname', 'lastname', 'Department', 'active'];
    }


    protected function configureBatchActions(array $actions): array
    {
        if (
            $this->hasRoute('edit') && $this->hasAccess('list')
        ) {
            $actions['barcodes'] = [
                'ask_confirmation' => false,
                'controller' => 'Shared\Controller\CostumerCRUDController::batchActionBarcodes',
            ];

            $actions['export_names'] = [
                'label' => 'Teilnehmer (XLSX)',
                'ask_confirmation' => false,
                'controller' => 'Shared\Controller\CostumerCRUDController::batchActionExportNames',
            ];
        }

        return $actions;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('firstname')
            ->add('lastname')
            ->add('active', null, ['data' => true])
            ->add('enddate', DatePickerType::class, [
                'widget' => 'single_text',
                'help' => '(Format: dd.mm.yyyy)'])
            ->add('Department', ChoiceType::class, [
                'choices' => Costumer::DEPARTMENTS,
            ])
             ->add('tags', ModelType::class, [
                'label' => 'Tags',
                'btn_add' => false,
                'required' => true,
                'multiple' => true,
                'placeholder' => 'Select tags',
                'btn_add'=>'new tag',
                'property' => 'name',
            ])
            
        ;
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('firstname')
            ->add('lastname')
            ->add('active')
            ->add('enddate', null, [
                'format' => 'd.m.Y',
            ])
            ->add('Barcode', 'barcode')             // custom types defined in config/packages/sonata_doctrine_orm_admin.yaml
            ->add('Department')
        ;
    }
}
