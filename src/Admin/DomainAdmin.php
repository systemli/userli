<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Domain;
use App\Event\DomainCreatedEvent;
use App\Validator\Lowercase;
use App\Validator\UniqueField;
use Override;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @extends Admin<Domain>
 */
final class DomainAdmin extends Admin
{
    private EventDispatcherInterface $eventDispatcher;

    #[Override]
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'domain';
    }

    #[Override]
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class, [
                'disabled' => !$this->isNewObject(),
                'constraints' => $this->isNewObject() ? [
                    new Assert\NotNull(),
                    new Assert\NotBlank(),
                    new Lowercase(),
                    new UniqueField(entityClass: Domain::class, field: 'name', message: 'form.unique-field'),
                ] : [],
            ]);
    }

    #[Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name');
    }

    #[Override]
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

    #[Override]
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('delete');
    }

    #[Override]
    protected function postPersist(object $object): void
    {
        $this->eventDispatcher->dispatch(new DomainCreatedEvent($object), DomainCreatedEvent::NAME);
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
