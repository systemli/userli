<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Enum\WebhookEvent;
use App\Event\UserEvent;
use App\EventListener\WebhookListener;
use App\Service\WebhookDispatcher;
use PHPUnit\Framework\TestCase;

class WebhookListenerTest extends TestCase
{
    use EventListenerAttributes;

    public function testOnUserCreated(): void
    {
        $user = new User('test@example.org');
        $dispatcher = $this->createMock(WebhookDispatcher::class);
        $dispatcher->expects($this->once())->method('dispatchUserEvent')->with($user, WebhookEvent::USER_CREATED);
        $listener = new WebhookListener($dispatcher);
        $listener->onUserCreated(new UserEvent($user));
    }

    public function testOnUserDeleted(): void
    {
        $user = new User('test@example.org');
        $dispatcher = $this->createMock(WebhookDispatcher::class);
        $dispatcher->expects($this->once())->method('dispatchUserEvent')->with($user, WebhookEvent::USER_DELETED);
        $listener = new WebhookListener($dispatcher);
        $listener->onUserDeleted(new UserEvent($user));
    }

    public function testOnUserReset(): void
    {
        $user = new User('test@example.org');
        $dispatcher = $this->createMock(WebhookDispatcher::class);
        $dispatcher->expects($this->once())->method('dispatchUserEvent')->with($user, WebhookEvent::USER_RESET);
        $listener = new WebhookListener($dispatcher);
        $listener->onUserReset(new UserEvent($user));
    }

    public function testListensToEvents(): void
    {
        self::assertEquals([
            UserEvent::USER_CREATED => ['onUserCreated', 0],
            UserEvent::USER_DELETED => ['onUserDeleted', 0],
            UserEvent::USER_RESET => ['onUserReset', 0],
        ], self::listenedEvents(WebhookListener::class));
    }
}
