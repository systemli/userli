<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Roles;
use App\Repository\UserRepository;
use App\Traits\CreationTimeTrait;
use App\Traits\DeleteTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\EmailTrait;
use App\Traits\IdTrait;
use App\Traits\InvitationVoucherTrait;
use App\Traits\LastLoginTimeTrait;
use App\Traits\MailCryptEnabledTrait;
use App\Traits\MailCryptPublicKeyTrait;
use App\Traits\MailCryptSecretBoxTrait;
use App\Traits\OpenPgpKeyAwareTrait;
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
use App\Validator\EmailDomain;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Override;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Stringable;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'virtual_users')]
#[Index(columns: ['email'], name: 'email_idx')]
#[Index(columns: ['creation_time'], name: 'creation_time_idx')]
#[Index(columns: ['deleted'], name: 'deleted_idx')]
#[Index(columns: ['email', 'deleted'], name: 'email_deleted_idx')]
#[Index(columns: ['domain_id', 'deleted'], name: 'domain_deleted_idx')]
#[Index(columns: ['email', 'domain_id'], name: 'email_domain_idx')]
#[EmailDomain]
class User implements UserInterface, PasswordAuthenticatedUserInterface, PasswordHasherAwareInterface, TwoFactorInterface, BackupCodeInterface, Stringable
{
    use CreationTimeTrait;
    use DeleteTrait;
    use DomainAwareTrait;
    use EmailTrait;
    use IdTrait;
    use InvitationVoucherTrait;
    use LastLoginTimeTrait;
    use MailCryptEnabledTrait;
    use MailCryptPublicKeyTrait;
    use MailCryptSecretBoxTrait;
    use OpenPgpKeyAwareTrait;
    use PasswordTrait;
    use PasswordVersionTrait;
    use PlainMailCryptPrivateKeyTrait;
    use PlainPasswordTrait;
    use PlainRecoveryTokenTrait;
    use QuotaTrait;
    use RecoverySecretBoxTrait;
    use RecoveryStartTimeTrait;
    use SaltTrait;
    use TwofactorBackupCodeTrait;
    use TwofactorTrait;
    use UpdatedTimeTrait;

    public const CURRENT_PASSWORD_VERSION = 2;

    #[ORM\Column(type: Types::ARRAY, options: ['default' => 'a:0:{}'])]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    private array $roles = [];

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $passwordChangeRequired;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->deleted = false;
        $this->passwordVersion = self::CURRENT_PASSWORD_VERSION;
        $this->passwordChangeRequired = false;
        $currentDateTime = new DateTime();
        $this->creationTime = $currentDateTime;
        $this->updatedTime = $currentDateTime;
    }

    #[Override]
    public function __toString(): string
    {
        return ($this->getEmail()) ?: '';
    }

    #[Override]
    public function getRoles(): array
    {
        return !empty($this->roles) ? $this->roles : [Roles::USER];
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @psalm-param 'ROLE_ADMIN'|'ROLE_SPAM'|'ROLE_SUSPICIOUS' $role
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function getUsername(): ?string
    {
        return $this->getUserIdentifier();
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }

    #[Override]
    public function getPasswordHasherName(): ?string
    {
        if ($this->getPasswordVersion() < self::CURRENT_PASSWORD_VERSION) {
            return 'legacy';
        }

        // use default encoder
        return null;
    }

    #[Override]
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
        $this->erasePlainMailCryptPrivateKey();
        $this->erasePlainRecoveryToken();
    }

    public function isPasswordChangeRequired(): bool
    {
        return $this->passwordChangeRequired;
    }

    public function setPasswordChangeRequired(bool $passwordChangeRequired): void
    {
        $this->passwordChangeRequired = $passwordChangeRequired;
    }
}
