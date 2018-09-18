<?php

namespace AppBundle\Admin;

use AppBundle\Creator\VoucherCodeCreator;
use AppBundle\Entity\Voucher;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author louis <louis@systemli.org>
 */
class VoucherAdmin extends Admin
{
    /**
     * {@inheritdoc}
     */
    protected $baseRoutePattern = 'voucher';
    /**
     * @var TokenStorageInterface
     */
    private $storage;

    /**
     * VoucherAdmin Constructor.
     *
     * @param string                $code
     * @param string                $class
     * @param string                $baseControllerName
     * @param TokenStorageInterface $storage
     */
    public function __construct($code, $class, $baseControllerName, TokenStorageInterface $storage)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'creationTime',
    );

    /**
     * {@inheritdoc}
     */
    public function getNewInstance()
    {
        /** @var Voucher $instance */
        $instance = parent::getNewInstance();
        $instance->setCode(VoucherCodeCreator::create());
        $user = $this->storage->getToken()->getUser();
        $instance->setUser($user);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $form)
    {
        $disabled = true;
        if ($this->hasAccess('list')) {
            $disabled = false;
        }

        $form
            ->add('user', null, array('disabled' => $disabled))
            ->add('code', null, array('disabled' => !$this->isNewObject()));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('id')
            ->add('code')
            ->add('user')
            ->add('creationTime')
            ->add('redeemedTime');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('user')
            ->add('code')
            ->add('created_since', 'doctrine_orm_callback', array(
                'callback' => function (ProxyQuery $proxyQuery, $alias, $field, $value) {
                    if (isset($value['value']) && null !== $value = $value['value']) {
                        /** @var QueryBuilder $qb */
                        $qb = $proxyQuery->getQueryBuilder();
                        $field = sprintf('%s.creationTime', $alias);
                        $qb->andWhere(sprintf('%s >= :datetime', $field))
                            ->setParameter('datetime', new \DateTime($value))
                            ->orderBy($field, 'DESC');
                    }

                    return true;
                },
                'field_type' => TextType::class,
            ))
            ->add('redeemed_since', 'doctrine_orm_callback', array(
                'callback' => function (ProxyQuery $proxyQuery, $alias, $field, $value) {
                    if (isset($value['value']) && null !== $value = $value['value']) {
                        /** @var QueryBuilder $qb */
                        $qb = $proxyQuery->getQueryBuilder();
                        $field = sprintf('%s.redeemedTime', $alias);
                        $qb->andWhere(sprintf('%s >= :datetime', $field))
                            ->setParameter('datetime', new \DateTime($value))
                            ->orderBy($field, 'DESC');
                    }

                    return true;
                },
                'field_type' => TextType::class,
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureBatchActions($actions)
    {
        return array();
    }
}
