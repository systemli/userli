<?php

namespace AppBundle\Entity;

use AppBundle\Enum\Roles;
use AppBundle\Traits\CreationTimeTrait;
use AppBundle\Traits\DeleteTrait;
use AppBundle\Traits\DomainAwareTrait;
use AppBundle\Traits\EmailTrait;
use AppBundle\Traits\IdTrait;
use AppBundle\Traits\InvitationVoucherTrait;
use AppBundle\Traits\LastLoginTimeTrait;
use AppBundle\Traits\PasswordTrait;
use AppBundle\Traits\PlainPasswordTrait;
use AppBundle\Traits\QuotaTrait;
use AppBundle\Traits\SaltTrait;
use AppBundle\Traits\UpdatedTimeTrait;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author louis <louis@systemli.org>
 */
class User implements UserInterface
{
    use IdTrait, CreationTimeTrait, UpdatedTimeTrait, EmailTrait, QuotaTrait, PasswordTrait, SaltTrait, DeleteTrait,
        InvitationVoucherTrait, PlainPasswordTrait, DomainAwareTrait, LastLoginTimeTrait;

    /**
     * @var array
     */
    private $roles = array();

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->deleted = false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ($this->getEmail()) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return !empty($this->roles) ? $this->roles : Roles::USER;
    }

    /**
     * @param array $roles
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    /**
     * @param $role
     *
     * @return bool
     */
    public function hasRole($role)
    {
        return in_array($role, $this->roles);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }
}
