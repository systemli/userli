<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\WebhookEvent;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class LoadWebhookData extends Fixture implements FixtureGroupInterface
{
    private const int DELIVERY_COUNT = 75;

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $endpoint = new WebhookEndpoint(
            'http://webhook:8080/e6fd49c8-7ecc-4b0a-8af1-a16f508e0f68',
            bin2hex(random_bytes(16))
        );
        $endpoint->setEvents([WebhookEvent::USER_CREATED->value, WebhookEvent::USER_DELETED->value, WebhookEvent::USER_RESET->value]);
        $endpoint->setEnabled(true);

        $manager->persist($endpoint);

        $events = WebhookEvent::cases();
        $statuses = ['success', 'failed'];

        for ($i = 0; $i < self::DELIVERY_COUNT; ++$i) {
            $event = $events[array_rand($events)];

            $requestBody = [
                'event' => $event->value,
                'timestamp' => time() - random_int(0, 86400 * 7),
                'data' => [
                    'user_id' => random_int(1, 1000),
                    'email' => sprintf('user%d@example.org', random_int(1, 1000)),
                ],
            ];

            $requestHeaders = [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => hash('sha256', json_encode($requestBody).$endpoint->getSecret()),
                'X-Webhook-Attempt' => '1',
            ];

            $delivery = new WebhookDelivery($endpoint, $event, $requestBody, $requestHeaders);
            $this->applyRandomStatus($delivery, $statuses[array_rand($statuses)]);
            $manager->persist($delivery);

            if (($i % 50) === 0) {
                $manager->flush();
            }
        }

        $manager->flush();
        $manager->clear();
    }

    private function applyRandomStatus(WebhookDelivery $delivery, string $status): void
    {
        $delivery->setAttempts(random_int(1, 3));

        match ($status) {
            'success' => $this->applySuccess($delivery),
            'failed' => $this->applyFailed($delivery),
            default => null,
        };
    }

    private function applySuccess(WebhookDelivery $delivery): void
    {
        $delivery->setSuccess(true);
        $delivery->setResponseCode(200);
        $delivery->setResponseBody('{"status":"ok"}');
        $delivery->setDeliveredTime(new DateTimeImmutable(sprintf('-%d minutes', random_int(1, 1440))));
    }

    private function applyFailed(WebhookDelivery $delivery): void
    {
        $errorCodes = [400, 401, 403, 404, 500, 502, 503, 504];
        $errorResponses = ['{"error":"Bad Request"}', '{"error":"Internal Server Error"}', '502 Bad Gateway', ''];
        $errors = ['Connection timed out', 'Could not resolve host', 'Connection refused', 'HTTP error 500'];

        $delivery->setSuccess(false);
        $delivery->setResponseCode($errorCodes[array_rand($errorCodes)]);
        $delivery->setResponseBody($errorResponses[array_rand($errorResponses)]);
        $delivery->setError($errors[array_rand($errors)]);
        $delivery->setDeliveredTime(new DateTimeImmutable(sprintf('-%d minutes', random_int(1, 1440))));
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['basic', 'advanced'];
    }
}
