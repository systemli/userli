<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\WebhookEvent;
use App\Message\SendWebhook;
use App\MessageHandler\SendWebhookHandler;
use DateTimeImmutable;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Doctrine\ORM\EntityManagerInterface;

class SendWebhookHandlerTest extends TestCase
{
    private function createDelivery(): WebhookDelivery
    {
        $endpoint = new WebhookEndpoint('https://example.test/hook', 'sec');

        return new WebhookDelivery($endpoint, WebhookEvent::USER_CREATED, ['x' => 'y'], ['Content-Type' => 'application/json']);
    }

    public function testSuccessfulDelivery(): void
    {
        $delivery = $this->createDelivery();
        $id = (string)$delivery->getId();

        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->with($id)->willReturn($delivery);
        $repo->method('getClassName')->willReturn(WebhookDelivery::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->expects($this->never())->method('getContent');

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())->method('request')->willReturn($response);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->expects($this->once())->method('flush');

        $handler = new SendWebhookHandler($em, $http);
        $handler(new SendWebhook($id));

        $this->assertTrue($delivery->isSuccess());
        $this->assertEquals(200, $delivery->getResponseCode());
        $this->assertNull($delivery->getResponseBody());
        $this->assertEquals(1, $delivery->getAttempts());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDeliveredTime());
    }

    public function testFailedDeliveryOnException(): void
    {
        $delivery = $this->createDelivery();
        $id = (string)$delivery->getId();

        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->with($id)->willReturn($delivery);
        $repo->method('getClassName')->willReturn(WebhookDelivery::class);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())->method('request')->willThrowException(new \RuntimeException('Boom Failure Happens For A Very Long Error Message That Should Be Trimmed'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->expects($this->once())->method('flush');

        $handler = new SendWebhookHandler($em, $http);
        $handler(new SendWebhook($id));

        $this->assertFalse($delivery->isSuccess());
        $this->assertNotNull($delivery->getError());
        $this->assertEquals(1, $delivery->getAttempts());
        $this->assertInstanceOf(DateTimeImmutable::class, $delivery->getDeliveredTime());
    }

    public function testNoDeliveryFoundEarlyReturn(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('find')->willReturn(null);
        $repo->method('getClassName')->willReturn(WebhookDelivery::class);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->never())->method('request');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->expects($this->never())->method('flush');

        $handler = new SendWebhookHandler($em, $http);
        $handler(new SendWebhook('01HX0ZZZZZZZZZZZZZZZZZZZZZ'));
    }
}
