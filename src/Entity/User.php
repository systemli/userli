<?php

namespace App\Entity;

use Stringable;
use DateTime;
use App\Enum\Roles;
use App\Traits\CreationTimeTrait;
use App\Traits\DeleteTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\EmailTrait;
use App\Traits\IdTrait;
use App\Traits\InvitationVoucherTrait;
use App\Traits\LastLoginTimeTrait;
use App\Traits\MailCryptPublicKeyTrait;
use App\Traits\MailCryptSecretBoxTrait;
use App\Traits\MailCryptTrait;
use App\Traits\PasswordTrait;
use App\Traits\PasswordVersionTrait;
use App\Traits\PlainMailCryptPrivateKeyTrait;
use App\Traits\PlainPasswordTrait;
use App\Traits\PlainRecoveryTokenTrait;
use App\Traits\QuotaTrait;
use App\Traits\RecoverySecretBoxTrait;
use App\Traits\RecoveryStartTimeTrait;
use App\Traits\SaltTrait;
use App\Traits\TwofactorBackupCodeTrait;
use App\Traits\TwofactorTrait;
use App\Traits\UpdatedTimeTrait;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="virtual_users", indexes={
 *     @Index(name="email_idx", columns={"email"})
 * })
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, PasswordHasherAwareInterface, TwoFactorInterface, BackupCodeInterface, Stringable
{
    use IdTrait;
    use CreationTimeTrait;
    use UpdatedTimeTrait;
    use EmailTrait;
    use QuotaTrait;
    use PasswordTrait;
    use SaltTrait;
    use DeleteTrait;
    use InvitationVoucherTrait;
    use PlainPasswordTrait;
    use DomainAwareTrait;
    use LastLoginTimeTrait;
    use PasswordVersionTrait;
    use RecoverySecretBoxTrait;
    use PlainRecoveryTokenTrait;
    use RecoveryStartTimeTrait;
    use MailCryptTrait;
    use MailCryptSecretBoxTrait;
    use PlainMailCryptPrivateKeyTrait;
    use MailCryptPublicKeyTrait;
    use TwofactorTrait;
    use TwofactorBackupCodeTrait;

    public const CURRENT_PASSWORD_VERSION = 2;

    /** @ORM\Column(type="array") */
    private array $roles = [];

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->deleted = false;
        $this->passwordVersion = self::CURRENT_PASSWORD_VERSION;
        $currentDateTime = new DateTime();
        $this->creationTime = $currentDateTime;
        $this->updatedTime = $currentDateTime;
    }

    public function __toString(): string
    {
        return ($this->getEmail()) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return !empty($this->roles) ? $this->roles : [Roles::USER];
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @param $role
     *
     * @return bool
     */
    public function hasRole($role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): ?string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @return string
     */
    public function getUserIdentifier(): string {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function getPasswordHasherName(): ?string
    {
        if ($this->getPasswordVersion() < self::CURRENT_PASSWORD_VERSION) {
            return 'legacy';
        }

        // use default encoder
        return null;
    }
}
