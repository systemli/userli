<?php

namespace App\Admin;

use App\Helper\RandomStringGenerator;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class VoucherAdmin extends Admin
{
    protected array $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'creationTime',
    ];

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string {
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
            ->add('user', null, ['disabled' => $disabled])
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
            ->add('user')
            ->add('code')
            ->add('created_since', CallbackFilter::class, [
                'callback' => function (ProxyQuery $proxyQuery, $alias, $field, $value) {
                    if (isset($value['value']) && null !== $value = $value['value']) {
                        $qb = $proxyQuery->getQueryBuilder();
                        $field = sprintf('%s.creationTime', $alias);
                        $qb->andWhere(sprintf('%s >= :datetime', $field))
                            ->setParameter('datetime', new \DateTime($value))
                            ->orderBy($field, 'DESC');
                    }

                    return true;
                },
                'field_type' => TextType::class,
            ])
            ->add('redeemed_since', CallbackFilter::class, [
                'callback' => function (ProxyQuery $proxyQuery, $alias, $field, $value) {
                    if (isset($value['value']) && null !== $value = $value['value']) {
                        $qb = $proxyQuery->getQueryBuilder();
                        $field = sprintf('%s.redeemedTime', $alias);
                        $qb->andWhere(sprintf('%s >= :datetime', $field))
                            ->setParameter('datetime', new \DateTime($value))
                            ->orderBy($field, 'DESC');
                    }

                    return true;
                },
                'field_type' => TextType::class,
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
