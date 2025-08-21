<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;

class UserNotificationAdmin extends Admin
{
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'user-notification';
    }

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

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('user')
            ->add('type', null, [
                'label' => 'Type',
                'accessor' => function ($object) {
                    return $object->getType()->value;
                },
            ])
            ->add('creationTime');
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('edit');
    }
}
