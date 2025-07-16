<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\UserDeletedEvent;
use App\EventListener\UserDeletionListener;
use App\Handler\WebhookHandler;
use PHPUnit\Framework\TestCase;

class UserDeletedListenerTest extends TestCase
{
    public function testOnUserDeleted(): void
    {
        $user = new User();

        $webhookHandler = $this->createMock(WebhookHandler::class);
        $webhookHandler->expects($this->once())->method('send')->with($user, 'user.deleted');

        $listener = new UserDeletionListener($webhookHandler);
        $event = $this->createMock(UserDeletedEvent::class);
        $event->method('getUser')->willReturn($user);
        $listener->onUserDeleted($event);
    }
}
