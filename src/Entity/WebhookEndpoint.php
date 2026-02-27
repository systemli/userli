<?php

declare(strict_types=1);

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\UpdatedTimeTrait;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Target URL for webhook notifications about user lifecycle events.
 *
 * When a subscribed event occurs (e.g. user.created, user.deleted, user.reset),
 * a signed JSON POST request is dispatched to the configured URL. The shared secret
 * is used to compute an HMAC-SHA256 signature sent in the `X-Webhook-Signature` header.
 *
 * @see WebhookEvent for the available event types
 * @see WebhookDelivery for delivery attempt logs
 */
#[ORM\Entity]
#[ORM\Index(columns: ['enabled'])]
#[ORM\Table(name: 'webhook_endpoints')]
class WebhookEndpoint implements UpdatedTimeInterface
{
    use CreationTimeTrait;
    use UpdatedTimeTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 2048)]
    private string $url;

    /** Shared secret used for HMAC-SHA256 signing of webhook request bodies. */
    #[ORM\Column(length: 255)]
    private string $secret;

    /** Subscribed event types (values from {@see WebhookEvent}, e.g. "user.created"). */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $events = null;

    /** Whether deliveries are dispatched to this endpoint. Disabled endpoints are skipped. */
    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    public function __construct(string $url, string $secret)
    {
        $this->url = $url;
        $this->secret = $secret;
        $this->creationTime = new DateTimeImmutable();
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
}
