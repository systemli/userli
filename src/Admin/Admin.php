<?php

namespace App\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Symfony\Component\Security\Core\Security;

/**
 * @author louis <louis@systemli.org>
 */
abstract class Admin extends AbstractAdmin
{
    /**
     * @var Security
     */
    protected $security;

    /**
     * Admin constructor.
     * @param string   $code
     * @param string   $class
     * @param string   $baseControllerName
     * @param Security $security
     */
    public function __construct(string $code, string $class, string $baseControllerName, Security $security)
    {
        $this->security = $security;
        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * @return bool
     */
    protected function isNewObject()
    {
        return !$this->getRequest()->get($this->getIdParameter());
    }
}
