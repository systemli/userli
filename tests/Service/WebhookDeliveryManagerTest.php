<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\WebhookEvent;
use App\Message\SendWebhook;
use App\Repository\WebhookDeliveryRepository;
use App\Service\WebhookDeliveryManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class WebhookDeliveryManagerTest extends TestCase
{
    public function testFindPaginatedByEndpointReturnsDeliveries(): void
    {
        $endpoint = new WebhookEndpoint('https://example.test/a', 'secret');

        $d1 = new WebhookDelivery($endpoint, WebhookEvent::USER_CREATED, ['a' => 1], ['Content-Type' => 'application/json']);
        $d2 = new WebhookDelivery($endpoint, WebhookEvent::USER_DELETED, ['b' => 2], ['Content-Type' => 'application/json']);

        $repo = $this->createMock(WebhookDeliveryRepository::class);
        $repo->expects($this->once())
            ->method('countByEndpointAndStatus')
            ->with($endpoint, '')
            ->willReturn(2);
        $repo->expects($this->once())
            ->method('findByEndpointAndStatus')
            ->with($endpoint, '', 20, 0)
            ->willReturn([$d2, $d1]);

        $em = $this->createStub(EntityManagerInterface::class);
        $bus = $this->createStub(MessageBusInterface::class);

        $manager = new WebhookDeliveryManager($em, $bus, $repo);
        $result = $manager->findPaginatedByEndpoint($endpoint);

        self::assertSame([$d2, $d1], $result['items']);
        self::assertSame(1, $result['page']);
        self::assertSame(1, $result['totalPages']);
        self::assertSame(2, $result['total']);
    }

    public function testRetryDoesNothingForSuccessfulDelivery(): void
    {
        $endpoint = new WebhookEndpoint('https://example.test/a', 'secret');
        $delivery = new WebhookDelivery($endpoint, WebhookEvent::USER_CREATED, ['x' => 'y'], ['Content-Type' => 'application/json']);
        $delivery->setSuccess(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');
        $repo = $this->createStub(WebhookDeliveryRepository::class);

        $manager = new WebhookDeliveryManager($em, $bus, $repo);
        $manager->retry($delivery);
    }

    public function testRetryResetsAndDispatches(): void
    {
        $endpoint = new WebhookEndpoint('https://example.test/a', 'secret');
        $delivery = new WebhookDelivery($endpoint, WebhookEvent::USER_CREATED, ['x' => 'y'], ['Content-Type' => 'application/json']);
        // Simulate a previous failed attempt
        $delivery->setAttempts(1);
        $delivery->setResponseCode(500);
        $delivery->setResponseBody('error');
        $delivery->setError('Timeout');
        $delivery->setDeliveredTime(new DateTimeImmutable('-1 minute'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())->method('dispatch')->with($this->callback(static function ($message) use ($delivery) {
            if ($message instanceof Envelope) {
                $inner = $message->getMessage();
            } else {
                $inner = $message;
            }
            self::assertInstanceOf(SendWebhook::class, $inner);
            self::assertEquals((string) $delivery->getId(), $inner->deliveryId);

            return true;
        }))->willReturnCallback(static fn ($m) => $m instanceof Envelope ? $m : new Envelope($m));

        $repo = $this->createStub(WebhookDeliveryRepository::class);

        $manager = new WebhookDeliveryManager($em, $bus, $repo);
        $manager->retry($delivery);

        self::assertNull($delivery->getResponseCode());
        self::assertNull($delivery->getResponseBody());
        self::assertNull($delivery->getError());
        self::assertFalse($delivery->isSuccess());
        self::assertNull($delivery->getDeliveredTime());
        self::assertArrayHasKey('X-Webhook-Attempt', $delivery->getRequestHeaders());
        self::assertSame('2', $delivery->getRequestHeaders()['X-Webhook-Attempt']);
    }
}
