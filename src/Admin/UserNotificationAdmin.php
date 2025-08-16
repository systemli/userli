<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class UserNotificationAdmin extends Admin
{
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user.email', null, [
                'show_filter' => true,
            ])
            ->add('type', null, [
                'show_filter' => true,
            ])
            ->add('locale');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user')
            ->add('type', null, [
                'label' => 'Type',
                'accessor' => function ($object) {
                    return $object->getType()->value;
                },
            ])
            ->add('creationTime')
            ->add('locale');
    }
}
