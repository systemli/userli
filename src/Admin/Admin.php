<?php

declare(strict_types=1);

namespace App\Admin;

use Override;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;

/**
 * @template T of object
 *
 * @extends AbstractAdmin<T>
 */
abstract class Admin extends AbstractAdmin
{
    #[Override]
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    protected function isNewObject(): bool
    {
        return !$this->getRequest()->get($this->getIdParameter());
    }
}
