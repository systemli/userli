<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApiTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

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

    #[ORM\Column(type: 'json')]
    private array $scopes;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $creationTime;

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
