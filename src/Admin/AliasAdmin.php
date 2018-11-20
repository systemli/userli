<?php

namespace App\Admin;

use App\Entity\Alias;
use App\Entity\User;
use App\Handler\DeleteHandler;
use App\Traits\DomainGuesserAwareTrait;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * @author louis <louis@systemli.org>
 */
class AliasAdmin extends Admin
{
    use DomainGuesserAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $baseRoutePattern = 'alias';

    /**
     * @var DeleteHandler
     */
    private $deleteHandler;

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('user', EntityType::class, ['class' => User::class, 'required' => false])
            ->add('source', EmailType::class)
            ->add('destination', EmailType::class, ['required' => false])
            ->add('deleted', CheckboxType::class, ['disabled' => true]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('user', null, [
                'show_filter' => true,
            ])
            ->add('source', null, [
                'show_filter' => true,
            ])
            ->add('domain', null, [
                'show_filter' => true,
            ])
            ->add('deleted', 'doctrine_orm_choice', [
                'field_options' => [
                    'required' => false,
                    'choices' => [0 => 'No', 1 => 'Yes'],
                ],
                'field_type' => ChoiceType::class,
                'show_filter' => true,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('source')
            ->addIdentifier('destination')
            ->addIdentifier('user')
            ->add('domain')
            ->add('creationTime')
            ->add('updatedTime')
            ->add('deleted');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureBatchActions($actions)
    {
        return [];
    }

    /**
     * @param Alias $alias
     */
    public function prePersist($alias)
    {
        if (null == $alias->getDestination()) {
            if (null == $alias->getUser()) {
                // set user_id to current user if neither destination nor user_id is given
                $alias->setUser($this->security->getUser());
            }
            $alias->setDestination($alias->getUser());
        }

        if (null !== $domain = $this->getDomainGuesser()->guess($alias->getSource())) {
            $alias->setDomain($domain);
        }
    }

    /**
     * @param Alias $alias
     */
    public function preUpdate($alias)
    {
        $alias->setUpdatedTime(new \DateTime());
        if (null == $alias->getDestination()) {
            $alias->setDestination($alias->getUser());
        }
        if (null !== $domain = $this->getDomainGuesser()->guess($alias->getSource())) {
            $alias->setDomain($domain);
        }
    }

    /**
     * @param Alias $alias
     */
    public function delete($alias)
    {
        $this->deleteHandler->deleteAlias($alias);
    }

    /**
     * @param DeleteHandler $deleteHandler
     */
    public function setDeleteHandler(DeleteHandler $deleteHandler)
    {
        $this->deleteHandler = $deleteHandler;
    }
}
