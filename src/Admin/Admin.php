<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Symfony\Bundle\SecurityBundle\Security;

abstract class Admin extends AbstractAdmin
{
    /**
     * Admin constructor.
     */
    public function __construct(protected Security $security)
    {
        parent::__construct();
    }

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
