<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Message\SendWebhook;
use App\Repository\WebhookDeliveryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class WebhookDeliveryManager
{
    private const int PAGE_SIZE = 20;

    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $bus,
        private WebhookDeliveryRepository $repository,
    ) {
    }

    /**
     * Find deliveries with offset-based pagination.
     *
     * @return array{items: WebhookDelivery[], page: int, totalPages: int, total: int}
     */
    public function findPaginatedByEndpoint(
        WebhookEndpoint $endpoint,
        int $page = 1,
        string $status = '',
        string $eventType = '',
    ): array {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $total = $this->repository->countByEndpointAndStatus($endpoint, $status, $eventType);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $items = $this->repository->findByEndpointAndStatus(
            $endpoint,
            $status,
            self::PAGE_SIZE,
            $offset,
            $eventType,
        );

        return [
            'items' => $items,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ];
    }

    public function retry(WebhookDelivery $delivery): void
    {
        if ($delivery->isSuccess()) {
            return;
        }

        // Reset mutable result fields
        $delivery->setResponseCode(null);
        $delivery->setResponseBody(null);
        $delivery->setError(null);
        $delivery->setSuccess(false);
        $delivery->setDeliveredTime(null);

        // Annotate attempt header (next attempt number is current attempts + 1, increment happens in handler)
        $headers = $delivery->getRequestHeaders();
        $headers['X-Webhook-Attempt'] = (string) ($delivery->getAttempts() + 1);
        $delivery->setRequestHeaders($headers);

        $this->em->flush();

        $this->bus->dispatch(new SendWebhook((string) $delivery->getId()));
    }
}
