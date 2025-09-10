<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\WebhookEvent;
use App\Message\SendWebhook;
use App\Service\WebhookDeliveryManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;
use Doctrine\ORM\EntityManagerInterface;

class WebhookDeliveryManagerTest extends TestCase
{
    public function testFindAllByEndpointReturnsDeliveries(): void
    {
        $endpoint = new WebhookEndpoint('https://example.test/a', 'secret');

        $d1 = new WebhookDelivery($endpoint, WebhookEvent::USER_CREATED, ['a' => 1], ['Content-Type' => 'application/json']);
        $d2 = new WebhookDelivery($endpoint, WebhookEvent::USER_DELETED, ['b' => 2], ['Content-Type' => 'application/json']);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('getClassName')->willReturn(WebhookDelivery::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['endpoint' => $endpoint], ['id' => 'DESC'])
            ->willReturn([$d2, $d1]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(WebhookDelivery::class)->willReturn($repo);
        $bus = $this->createMock(MessageBusInterface::class);

        $manager = new WebhookDeliveryManager($em, $bus);
        $result = $manager->findAllByEndpoint($endpoint);

        $this->assertSame([$d2, $d1], $result);
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

        $manager = new WebhookDeliveryManager($em, $bus);
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
        $delivery->setDeliveredTime(new \DateTimeImmutable('-1 minute'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())->method('dispatch')->with($this->callback(function ($message) use ($delivery) {
            if ($message instanceof Envelope) {
                $inner = $message->getMessage();
            } else {
                $inner = $message;
            }
            $this->assertInstanceOf(SendWebhook::class, $inner);
            $this->assertEquals((string)$delivery->getId(), $inner->deliveryId);
            return true;
        }))->willReturnCallback(fn($m) => $m instanceof Envelope ? $m : new Envelope($m));

        $manager = new WebhookDeliveryManager($em, $bus);
        $manager->retry($delivery);

        $this->assertNull($delivery->getResponseCode());
        $this->assertNull($delivery->getResponseBody());
        $this->assertNull($delivery->getError());
        $this->assertFalse($delivery->isSuccess());
        $this->assertNull($delivery->getDeliveredTime());
        $this->assertArrayHasKey('X-Webhook-Attempt', $delivery->getRequestHeaders());
        $this->assertSame('2', $delivery->getRequestHeaders()['X-Webhook-Attempt']);
    }
}
