<?php

namespace App\Admin;

use App\Entity\Alias;
use App\Entity\User;
use App\Enum\Roles;
use App\Traits\DomainGuesserAwareTrait;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class AliasAdmin extends Admin
{
    use DomainGuesserAwareTrait;

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'alias';
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('source', EmailType::class)
            ->add('user', EntityType::class, ['class' => User::class, 'required' => false])
            ->add('deleted', CheckboxType::class, ['disabled' => true]);

        if ($this->security->isGranted(Roles::ADMIN)) {
            $form
                ->add('destination', EmailType::class, ['required' => false]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('source')
            ->add('user.email', null, ['label' => 'User'])
            ->add('domain')
            ->add('deleted');
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
            ->addIdentifier('source', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->addIdentifier('destination', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->addIdentifier('user', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->add('domain')
            ->add('creationTime')
            ->add('updatedTime')
            ->add('deleted');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureBatchActions($actions): array
    {
        return [];
    }

    /**
     * @param Alias $object
     */
    public function prePersist($object): void
    {
        if (null === $object->getDestination()) {
            if (null === $object->getUser()) {
                // set user_id to current user if neither destination nor user_id is given
                $object->setUser($this->security->getUser());
            }
            $object->setDestination($object->getUser());
        }

        if (null !== $domain = $this->getDomainGuesser()->guess($object->getSource())) {
            $object->setDomain($domain);
        }
    }

    /**
     * @param Alias $object
     */
    public function preUpdate($object): void
    {
        $object->setUpdatedTime(new \DateTime());
        if (null === $object->getDestination()) {
            $object->setDestination($object->getUser());
        }
        if (null !== $domain = $this->getDomainGuesser()->guess($object->getSource())) {
            $object->setDomain($domain);
        }

        // domain admins are only allowed to set alias to existing user
        if (!$this->security->isGranted(Roles::ADMIN)) {
            $object->setDestination($object->getUser());
        }
    }
}
