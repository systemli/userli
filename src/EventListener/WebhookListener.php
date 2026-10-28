<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\WebhookEvent;
use App\Event\UserEvent;
use App\Service\WebhookDispatcher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class WebhookListener
{
    public function __construct(private WebhookDispatcher $dispatcher)
    {
    }

    #[AsEventListener(event: UserEvent::USER_CREATED)]
    public function onUserCreated(UserEvent $event): void
    {
        $this->dispatcher->dispatchUserEvent($event->getUser(), WebhookEvent::USER_CREATED);
    }

    #[AsEventListener(event: UserEvent::USER_DELETED)]
    public function onUserDeleted(UserEvent $event): void
    {
        $this->dispatcher->dispatchUserEvent($event->getUser(), WebhookEvent::USER_DELETED);
    }

    #[AsEventListener(event: UserEvent::USER_RESET)]
    public function onUserReset(UserEvent $event): void
    {
        $this->dispatcher->dispatchUserEvent($event->getUser(), WebhookEvent::USER_RESET);
    }
}
