<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\WebhookEvent;
use App\Message\SendWebhook;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

use const DATE_ATOM;
use const JSON_UNESCAPED_SLASHES;

final readonly class WebhookDispatcher
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $bus,
    ) {
    }

    public function dispatchUserEvent(User $user, WebhookEvent $type): void
    {
        $endpoints = $this->em->getRepository(WebhookEndpoint::class)->findBy(['enabled' => true]);

        $payload = [
            'type' => $type->value,
            'timestamp' => (new DateTimeImmutable())->format(DATE_ATOM),
            'data' => [
                'email' => $user->getEmail(),
            ],
        ];

        foreach ($endpoints as $endpoint) {
            if ($endpoint->getEvents() && !in_array($type->value, $endpoint->getEvents(), true)) {
                continue;
            }

            $data = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $signature = hash_hmac('sha256', $data, $endpoint->getSecret());
            $headers = [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Attempt' => '1',
            ];

            $delivery = new WebhookDelivery($endpoint, $type, $payload, $headers);

            $this->em->persist($delivery);
            $this->em->flush();

            $delivery->setRequestHeaders(array_merge($headers, ['X-Webhook-Id' => (string) $delivery->getId()]));

            $this->em->flush();

            $this->bus->dispatch(new SendWebhook((string) $delivery->getId()));
        }
    }
}
