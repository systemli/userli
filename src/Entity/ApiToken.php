<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApiTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Bearer token for authenticating API requests.
 *
 * Each token is scoped to one or more API integrations (e.g. dovecot, postfix, keycloak).
 * The plain token is shown only once at creation time; only a SHA-256 hash is persisted.
 * Sent via `Authorization: Bearer <token>` or the `X-API-Token` header.
 *
 * @see ApiScope for the available scope values
 */
#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
#[ORM\Index(columns: ['token'], name: 'idx_token')]
#[ORM\Table(name: 'api_tokens')]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name;

    /** API scopes this token grants access to (values from {@see ApiScope}). */
    #[ORM\Column(type: 'json')]
    private array $scopes;

    /** SHA-256 hash of the plain bearer token. */
    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $creationTime;

    /** Automatically updated on each successful API request authenticated with this token. */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastUsedTime = null;

    public function __construct(string $token, string $name, array $scopes)
    {
        $this->token = $token;
        $this->name = $name;
        $this->scopes = $scopes;
        $this->creationTime = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getCreationTime(): DateTimeImmutable
    {
        return $this->creationTime;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getLastUsedTime(): ?DateTimeImmutable
    {
        return $this->lastUsedTime;
    }

    public function setLastUsedTime(?DateTimeImmutable $lastUsedTime): void
    {
        $this->lastUsedTime = $lastUsedTime;
    }
}
