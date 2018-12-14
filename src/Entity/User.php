<?php

namespace App\Entity;

use App\Enum\Roles;
use App\Traits\CreationTimeTrait;
use App\Traits\DeleteTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\EmailTrait;
use App\Traits\IdTrait;
use App\Traits\InvitationVoucherTrait;
use App\Traits\LastLoginTimeTrait;
use App\Traits\PasswordTrait;
use App\Traits\PlainPasswordTrait;
use App\Traits\PlainRecoveryTokenTrait;
use App\Traits\QuotaTrait;
use App\Traits\RecoveryCipherTrait;
use App\Traits\SaltTrait;
use App\Traits\UpdatedTimeTrait;
use App\Traits\PasswordVersionTrait;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author louis <louis@systemli.org>
 */
class User implements UserInterface, EncoderAwareInterface
{
    use IdTrait, CreationTimeTrait, UpdatedTimeTrait, EmailTrait, QuotaTrait, PasswordTrait, SaltTrait, DeleteTrait,
        InvitationVoucherTrait, PlainPasswordTrait, DomainAwareTrait, LastLoginTimeTrait, PasswordVersionTrait,
        RecoveryCipherTrait, PlainRecoveryTokenTrait;

    const CURRENT_PASSWORD_VERSION = 2;

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
        $this->passwordVersion = self::CURRENT_PASSWORD_VERSION;
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
        return !empty($this->roles) ? $this->roles : [Roles::USER];
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
        return in_array($role, $this->getRoles());
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
    public function getEncoderName()
    {
        if ($this->getPasswordVersion() < self::CURRENT_PASSWORD_VERSION) {
            return 'legacy';
        }

        // use default encoder
        return null;
    }
}
