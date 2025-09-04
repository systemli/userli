<?php

namespace App\Service;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Message\SendWebhook;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class WebhookDeliveryManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface    $bus
    )
    {
    }

    public function findAllByEndpoint(WebhookEndpoint $endpoint): array
    {
        return $this->em->getRepository(WebhookDelivery::class)->findBy(
            ['endpoint' => $endpoint],
            ['id' => 'DESC']
        );
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
        $headers['X-Webhook-Attempt'] = (string)($delivery->getAttempts() + 1);
        $delivery->setRequestHeaders($headers);

        $this->em->flush();

        $this->bus->dispatch(new SendWebhook((string)$delivery->getId()));
    }
}
