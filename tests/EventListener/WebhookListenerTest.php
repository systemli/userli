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

    public function testGetSubscribedEvents(): void
    {
        $events = WebhookListener::getSubscribedEvents();
        self::assertArrayHasKey(UserEvent::USER_CREATED, $events);
        self::assertArrayHasKey(UserEvent::USER_DELETED, $events);
        self::assertArrayHasKey(UserEvent::USER_RESET, $events);
        self::assertEquals('onUserCreated', $events[UserEvent::USER_CREATED]);
        self::assertEquals('onUserDeleted', $events[UserEvent::USER_DELETED]);
        self::assertEquals('onUserReset', $events[UserEvent::USER_RESET]);
    }
}
