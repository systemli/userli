<?php

namespace App\Admin;

use App\Entity\User;
use App\Enum\Roles;
use App\Handler\DeleteHandler;
use App\Helper\PasswordUpdater;
use App\Traits\DomainGuesserAwareTrait;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @author louis <louis@systemli.org>
 */
class UserAdmin extends Admin
{
    use DomainGuesserAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $baseRoutePattern = 'user';

    /**
     * {@inheritdoc}
     */
    protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'creationTime',
    );

    /** @var PasswordUpdater */
    private $passwordUpdater;
    /** @var DeleteHandler */
    private $deleteHandler;

    /**
     * {@inheritdoc}
     */
    public function getNewInstance()
    {
        /** @var User $instance */
        $instance = parent::getNewInstance();

        $instance->setRoles([Roles::USER]);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('email', EmailType::class, array('disabled' => !$this->isNewObject()))
            ->add('plainPassword', PasswordType::class, array('label' => 'form.password', 'required' => $this->isNewObject()))
            ->add('roles', ChoiceType::class, array(
                'choices' => [Roles::getAll()],
                'multiple' => true,
                'expanded' => true,
                'label' => 'form.roles',
            ))
            ->add('quota')
            ->add('deleted');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('email', null, array(
                'show_filter' => true,
            ))
            ->add('domain', null, array(
                'show_filter' => false,
            ))
            ->add('registration_since', 'doctrine_orm_callback', array(
                'callback' => function (ProxyQuery $proxyQuery, $alias, $field, $value) {
                    if (isset($value['value']) && null !== $value = $value['value']) {
                        /** @var QueryBuilder $qb */
                        $qb = $proxyQuery->getQueryBuilder();
                        $field = sprintf('%s.creationTime', $alias);
                        $qb->andWhere(sprintf('%s >= :datetime', $field))
                            ->setParameter('datetime', new DateTime($value))
                            ->orderBy($field, 'DESC');
                    }

                    return true;
                },
                'field_type' => TextType::class,
                'show_filter' => true,
            ))
            ->add('roles', null, array(
                'field_options' => array(
                    'required' => false,
                    'choices' => [Roles::getAll()],
                ),
                'field_type' => ChoiceType::class,
                'show_filter' => true,
            ))
            ->add('deleted', 'doctrine_orm_choice', array(
                'field_options' => array(
                    'required' => false,
                    'choices' => array(0 => 'No', 1 => 'Yes'),
                ),
                'field_type' => ChoiceType::class,
                'show_filter' => true,
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('email')
            ->add('creationTime')
            ->add('updatedTime')
            ->add('deleted');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureBatchActions($actions)
    {
        if ($this->hasRoute('edit') && $this->hasAccess('edit')) {
            $actions['remove_vouchers'] = array(
                'ask_confirmation' => true,
            );
        }

        return $actions;
    }

    /**
     * @param User $user
     */
    public function prePersist($user)
    {
        $this->passwordUpdater->updatePassword($user);

        if (null === $user->getDomain() && null !== $domain = $this->domainGuesser->guess($user->getEmail())) {
            $user->setDomain($domain);
        }
    }

    /**
     * @param User $user
     */
    public function preUpdate($user)
    {
        if (!empty($user->getPlainPassword())) {
            $this->passwordUpdater->updatePassword($user);
        }
    }

    /**
     * @param User $user
     */
    public function delete($user)
    {
        if (!$user instanceof User) {
            throw new RuntimeException('The object is not a User');
        }

        $this->deleteHandler->deleteUser($user);
    }

    /**
     * @param PasswordUpdater $passwordUpdater
     */
    public function setPasswordUpdater(PasswordUpdater $passwordUpdater)
    {
        $this->passwordUpdater = $passwordUpdater;
    }

    /**
     * @param DeleteHandler $deleteHandler
     */
    public function setDeleteHandler(DeleteHandler $deleteHandler)
    {
        $this->deleteHandler = $deleteHandler;
    }
}
