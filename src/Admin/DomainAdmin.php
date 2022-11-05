<?php

namespace App\Admin;

use App\Creator\DomainCreator;
use App\Event\DomainCreatedEvent;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DomainAdmin extends Admin
{
    private DomainCreator $domainCreator;
    private EventDispatcherInterface $eventDispatcher;

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string {
        return 'domain';
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class, ['disabled' => !$this->isNewObject()]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name');
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
            ->addIdentifier('name', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->add('creationTime')
            ->add('updatedTime');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('delete');
    }

    protected function prePersist(object $object): void
    {
        $this->domainCreator->validate($object, ['Default', 'unique']);
    }

    protected function postPersist(object $object): void
    {
        $this->eventDispatcher->dispatch(new DomainCreatedEvent($object), DomainCreatedEvent::NAME);
    }

    public function setDomainCreator(DomainCreator $domainCreator): void
    {
        $this->domainCreator = $domainCreator;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
