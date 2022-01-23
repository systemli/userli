<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Symfony\Component\Security\Core\Security;

abstract class Admin extends AbstractAdmin
{
    /**
     * @var Security
     */
    protected $security;

    /**
     * Admin constructor.
     */
    public function __construct(string $code, string $class, string $baseControllerName, Security $security)
    {
        $this->security = $security;
        parent::__construct($code, $class, $baseControllerName);
    }

    protected function isNewObject(): bool
    {
        return !$this->getRequest()->get($this->getIdParameter());
    }
}
