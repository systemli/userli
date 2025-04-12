<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\WebhookHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WebhookHandlerTest extends TestCase
{
    private User $user;
    private HTTPClientInterface $client;

    public function setUp(): void
    {
        $this->user = new User();
        $this->user->setEmail('test@example.org');

        $this->client = $this->createMock(HttpClientInterface::class);

    }

    public function testSendWithoutWebhook(): void
    {
        $webhookUrl = '';
        $webhookSecret = '';
        $webhookHandler = new WebhookHandler($this->client, $webhookUrl, $webhookSecret);

        $this->client->expects(self::never())
            ->method('request');
        $webhookHandler->send($this->user, 'user.created');
    }

    public function testSend(): void
    {
        $webhookUrl = 'http://localhost:8080/userli';
        $webhookSecret = 'secret';
        $webhookHandler = new WebhookHandler($this->client, $webhookUrl, $webhookSecret);

        $response = $this->createMock(ResponseInterface::class);
        $this->client->expects(self::once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $opts) use ($webhookUrl, $response) {
                self::assertEquals('POST', $method);
                self::assertEquals($webhookUrl, $url);
                self::assertEquals('user.created', $opts['json']['type']);
                self::assertEquals('test@example.org', $opts['json']['data']['email']);
                self::assertNotEmpty($opts['headers']['X-Signature']);
                return $response;
            });
        $webhookHandler->send($this->user, 'user.created');
    }

    public function testSendWithException(): void
    {
        $webhookUrl = 'http://localhost:8080/userli';
        $webhookSecret = 'secret';
        $webhookHandler = new WebhookHandler($this->client, $webhookUrl, $webhookSecret);

        $this->createMock(ResponseInterface::class);
        $this->client->expects(self::once())
            ->method('request')
            ->willThrowException(new \RuntimeException());

        $webhookHandler->send($this->user, 'user.created');
    }
}
