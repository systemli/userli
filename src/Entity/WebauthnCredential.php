<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WebauthnCredentialRepository;
use App\Traits\CreationTimeTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A WebAuthn credential (security key or platform authenticator) used as second factor.
 *
 * Binary values (credential id, public key, user handle) are stored base64url encoded.
 */
#[ORM\Entity(repositoryClass: WebauthnCredentialRepository::class)]
#[ORM\Table(name: 'webauthn_credentials')]
#[ORM\UniqueConstraint(name: 'UNIQ_WEBAUTHN_CREDENTIAL_ID_HASH', columns: ['credential_id_hash'])]
#[ORM\UniqueConstraint(name: 'UNIQ_WEBAUTHN_CREDENTIAL_USER_NAME', columns: ['user_id', 'name'])]
class WebauthnCredential
{
    use CreationTimeTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'webauthnCredentials')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 64)]
    private string $name;

    // Credential ids can be up to 1023 bytes, so lookups and uniqueness go through a hash
    #[ORM\Column(type: Types::TEXT)]
    private string $credentialId;

    #[ORM\Column(length: 64)]
    private string $credentialIdHash;

    #[ORM\Column(type: Types::TEXT)]
    private string $publicKey;

    #[ORM\Column(length: 255)]
    private string $userHandle;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    private int $signCount;

    /** @var string[] */
    #[ORM\Column(type: Types::JSON)]
    private array $transports;

    #[ORM\Column(length: 36)]
    private string $aaguid;

    #[ORM\Column(length: 32)]
    private string $attestationType;

    #[ORM\Column(nullable: true)]
    private ?bool $backupEligible;

    #[ORM\Column(nullable: true)]
    private ?bool $backupStatus;

    #[ORM\Column(nullable: true)]
    private ?bool $uvInitialized;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastUsedTime = null;

    /**
     * @param string[] $transports
     */
    public function __construct(
        User $user,
        string $name,
        string $credentialId,
        string $publicKey,
        string $userHandle,
        int $signCount,
        array $transports,
        string $aaguid,
        string $attestationType,
        ?bool $backupEligible,
        ?bool $backupStatus,
        ?bool $uvInitialized,
    ) {
        $this->user = $user;
        $this->name = $name;
        $this->credentialId = $credentialId;
        $this->credentialIdHash = self::hashCredentialId($credentialId);
        $this->publicKey = $publicKey;
        $this->userHandle = $userHandle;
        $this->signCount = $signCount;
        $this->transports = $transports;
        $this->aaguid = $aaguid;
        $this->attestationType = $attestationType;
        $this->backupEligible = $backupEligible;
        $this->backupStatus = $backupStatus;
        $this->uvInitialized = $uvInitialized;
        $this->creationTime = new DateTimeImmutable();
    }

    public static function hashCredentialId(string $credentialId): string
    {
        return hash('sha256', $credentialId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getUserHandle(): string
    {
        return $this->userHandle;
    }

    public function getSignCount(): int
    {
        return $this->signCount;
    }

    /**
     * @return string[]
     */
    public function getTransports(): array
    {
        return $this->transports;
    }

    public function getAaguid(): string
    {
        return $this->aaguid;
    }

    public function getAttestationType(): string
    {
        return $this->attestationType;
    }

    public function getBackupEligible(): ?bool
    {
        return $this->backupEligible;
    }

    public function getBackupStatus(): ?bool
    {
        return $this->backupStatus;
    }

    public function getUvInitialized(): ?bool
    {
        return $this->uvInitialized;
    }

    public function getLastUsedTime(): ?DateTimeImmutable
    {
        return $this->lastUsedTime;
    }

    /**
     * Records a successful assertion with the state reported by the authenticator.
     */
    public function recordUsage(int $signCount, ?bool $backupEligible, ?bool $backupStatus, ?bool $uvInitialized): void
    {
        $this->signCount = $signCount;
        $this->backupEligible = $backupEligible;
        $this->backupStatus = $backupStatus;
        $this->uvInitialized = $uvInitialized;
        $this->lastUsedTime = new DateTimeImmutable();
    }

    /**
     * Records a successful assertion that another relying party (e.g. Keycloak) verified.
     */
    public function recordSignCount(int $signCount): void
    {
        $this->signCount = $signCount;
        $this->lastUsedTime = new DateTimeImmutable();
    }
}
