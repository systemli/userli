<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\UserCreatedEvent;
use App\EventListener\UserCreatedListener;
use App\Handler\WebhookHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class UserCreatedListenerTest extends TestCase
{
    public function testOnUserCreated(): void
    {
        $user = new User();

        $webhookHandler = $this->createMock(WebhookHandler::class);
        $webhookHandler->expects($this->once())->method('send')->with($user, 'user.created');

        $listener = new UserCreatedListener($webhookHandler);
        $event = $this->createMock(UserCreatedEvent::class);
        $event->method('getUser')->willReturn($user);
        $listener->onUserCreated($event);
    }
}
