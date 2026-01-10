<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\WebhookEvent;
use App\Repository\WebhookDeliveryRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: WebhookDeliveryRepository::class)]
#[ORM\Table(name: 'webhook_deliveries')]
#[ORM\Index(columns: ['dispatched_time'])]
#[ORM\Index(columns: ['success'])]
#[ORM\Index(columns: ['type'])]
class WebhookDelivery
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: WebhookEndpoint::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WebhookEndpoint $endpoint;

    #[ORM\Column(length: 100)]
    private string $type;

    #[ORM\Column(type: 'json', options: ['jsonb' => true])]
    private array $requestBody;

    #[ORM\Column(type: 'json', options: ['jsonb' => true])]
    private array $requestHeaders;

    #[ORM\Column(nullable: true)]
    private ?int $responseCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $responseBody = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $success = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $dispatchedTime;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deliveredTime = null;

    public function __construct(WebhookEndpoint $endpoint, WebhookEvent $type, array $requestBody, array $requestHeaders)
    {
        $this->id = new Ulid();
        $this->endpoint = $endpoint;
        $this->type = $type->value;
        $this->requestBody = $requestBody;
        $this->requestHeaders = $requestHeaders;
        $this->dispatchedTime = new DateTimeImmutable();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getEndpoint(): WebhookEndpoint
    {
        return $this->endpoint;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    public function setResponseCode(?int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function setResponseBody(?string $responseBody): void
    {
        $this->responseBody = $responseBody;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    public function getDispatchedTime(): DateTimeImmutable
    {
        return $this->dispatchedTime;
    }

    public function getDeliveredTime(): ?DateTimeImmutable
    {
        return $this->deliveredTime;
    }

    public function setDeliveredTime(?DateTimeImmutable $deliveredTime): void
    {
        $this->deliveredTime = $deliveredTime;
    }

    public function getRequestBody(): array
    {
        return $this->requestBody;
    }

    public function setRequestBody(array $requestBody): void
    {
        $this->requestBody = $requestBody;
    }

    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    public function setRequestHeaders(array $requestHeaders): void
    {
        $this->requestHeaders = $requestHeaders;
    }
}
