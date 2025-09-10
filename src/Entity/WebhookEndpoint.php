<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(columns: ['enabled'])]
#[ORM\Table(name: 'webhook_endpoints')]
class WebhookEndpoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 2048)]
    private string $url;

    #[ORM\Column(length: 255)]
    private string $secret;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $events = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $creationTime;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedTime;

    public function __construct(string $url, string $secret)
    {
        $this->url = $url;
        $this->secret = $secret;
        $this->creationTime = new DateTimeImmutable();
        $this->updatedTime = $this->creationTime;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function getEvents(): ?array
    {
        return $this->events;
    }

    public function setEvents(?array $events): void
    {
        $this->events = $events;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getCreationTime(): DateTimeImmutable
    {
        return $this->creationTime;
    }

    public function getUpdatedTime(): DateTimeImmutable
    {
        return $this->updatedTime;
    }

    public function setUpdatedTime(DateTimeImmutable $updatedTime): void
    {
        $this->updatedTime = $updatedTime;
    }
}
