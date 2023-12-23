<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Symfony\Component\Security\Core\Security;

abstract class Admin extends AbstractAdmin
{
    protected Security $security;

    /**
     * Admin constructor.
     */
    public function __construct(string $code, string $class, string $baseControllerName, Security $security)
    {
        $this->security = $security;
        parent::__construct($code, $class, $baseControllerName);
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
