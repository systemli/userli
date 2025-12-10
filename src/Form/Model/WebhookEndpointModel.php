<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Enum\WebhookEvent;
use Symfony\Component\Validator\Constraints as Assert;

final class WebhookEndpointModel
{
    #[Assert\NotBlank]
    #[Assert\Url]
    #[Assert\Length(max: 2048)]
    private string $url;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $secret;

    #[Assert\Choice(callback: [WebhookEvent::class, 'all'], multiple: true)]
    #[Assert\Count(min: 1)]
    private array $events = [];

    private bool $enabled = true;

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

    public function getEvents(): array
    {
        return $this->events;
    }

    public function setEvents(array $events): void
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
