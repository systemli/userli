<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Enum\WebhookEvent;
use App\Message\SendWebhook;
use App\MessageHandler\SendWebhookHandler;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
        $id = (string) $delivery->getId();

        $repo = $this->createStub(EntityRepository::class);
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

        self::assertTrue($delivery->isSuccess());
        self::assertEquals(200, $delivery->getResponseCode());
        self::assertNull($delivery->getResponseBody());
        self::assertEquals(1, $delivery->getAttempts());
        self::assertInstanceOf(DateTimeImmutable::class, $delivery->getDeliveredTime());
    }

    public function testFailedDeliveryThrowsExceptionForRetry(): void
    {
        $delivery = $this->createDelivery();
        $id = (string) $delivery->getId();

        $repo = $this->createStub(EntityRepository::class);
        $repo->method('find')->with($id)->willReturn($delivery);
        $repo->method('getClassName')->willReturn(WebhookDelivery::class);

        $http = $this->createMock(HttpClientInterface::class);
        $http
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new RuntimeException('Boom Failure Happens For A Very Long Error Message That Should Be Trimmed'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->expects($this->once())->method('flush');

        $handler = new SendWebhookHandler($em, $http);

        $this->expectException(RuntimeException::class);

        $handler(new SendWebhook($id));
    }

    public function testNoDeliveryFoundEarlyReturn(): void
    {
        $repo = $this->createStub(EntityRepository::class);
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
