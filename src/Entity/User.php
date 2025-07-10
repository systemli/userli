<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\UserRepository;
use App\Traits\PlainPasswordTrait;
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
use App\Traits\MailCryptEnabledTrait;
use App\Traits\PasswordTrait;
use App\Traits\PasswordVersionTrait;
use App\Traits\PlainMailCryptPrivateKeyTrait;
use App\Traits\PlainRecoveryTokenTrait;
use App\Traits\QuotaTrait;
use App\Traits\RecoverySecretBoxTrait;
use App\Traits\RecoveryStartTimeTrait;
use App\Traits\SaltTrait;
use App\Traits\TwofactorBackupCodeTrait;
use App\Traits\TwofactorTrait;
use App\Traits\UpdatedTimeTrait;
use App\Validator\Constraints\EmailDomain;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'virtual_users')]
#[Index(name: 'email_idx', columns: ['email'])]
#[EmailDomain]
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
    use DomainAwareTrait;
    use LastLoginTimeTrait;
    use PasswordVersionTrait;
    use PlainPasswordTrait;
    use PlainRecoveryTokenTrait;
    use RecoverySecretBoxTrait;
    use RecoveryStartTimeTrait;
    use MailCryptEnabledTrait;
    use MailCryptSecretBoxTrait;
    use PlainMailCryptPrivateKeyTrait;
    use MailCryptPublicKeyTrait;
    use TwofactorTrait;
    use TwofactorBackupCodeTrait;

    public const CURRENT_PASSWORD_VERSION = 2;

    #[ORM\Column(type: Types::ARRAY, options: ['default' => 'a:0:{}'])]
    #[Assert\NotNull]
    #[Assert\NotBlank]
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
    public function getUserIdentifier(): string
    {
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

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
        $this->erasePlainMailCryptPrivateKey();
        $this->erasePlainRecoveryToken();
    }
}
