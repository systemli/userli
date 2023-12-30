<?php

namespace App\Admin;

use App\Helper\RandomStringGenerator;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\Form\Type\DateRangePickerType;

class VoucherAdmin extends Admin
{
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'voucher';
    }

    /**
     * {@inheritdoc}
     */
    public function alterNewInstance(object $object): void
    {
        $object->setUser($this->security->getUser());
        $object->setCode(RandomStringGenerator::generate(6, true));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $disabled = true;
        if ($this->hasAccess('list')) {
            $disabled = false;
        }

        $form
            ->add('user', ModelAutocompleteType::class, ['disabled' => $disabled, 'property' => 'email'])
            ->add('code', null, ['disabled' => !$this->isNewObject()]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->add('code')
            ->add('user')
            ->add('creationTime')
            ->add('redeemedTime');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user.email', null, ['label' => 'User'])
            ->add('code')
            ->add('creationTime', DateTimeRangeFilter::class, [
                'field_type' => DateRangePickerType::class,
                'field_options' => [
                    'field_options' => [
                        'format' => 'dd.MM.yyyy'
                    ]
                ]
            ])
            ->add('redeemedTime', DateTimeRangeFilter::class, [
                'field_type' => DateRangePickerType::class,
                'field_options' => [
                    'field_options' => [
                        'format' => 'dd.MM.yyyy'
                    ]
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureBatchActions($actions): array
    {
        return [];
    }
}
