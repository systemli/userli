<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\UserNotification;
use Override;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;

/**
 * @extends Admin<UserNotification>
 */
final class UserNotificationAdmin extends Admin
{
    #[Override]
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'user-notification';
    }

    #[Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user.email', null, [
                'show_filter' => true,
            ])
            ->add('type', null, [
                'show_filter' => true,
            ]);
    }

    #[Override]
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('user')
            ->add('type', null, [
                'label' => 'Type',
                'accessor' => static fn ($object) => $object->getType()->value,
            ])
            ->add('creationTime');
    }

    #[Override]
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('edit');
    }
}
