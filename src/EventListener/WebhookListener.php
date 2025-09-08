<?php

namespace App\EventListener;

use App\Enum\WebhookEvent;
use App\Event\UserEvent;
use App\Service\WebhookDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class WebhookListener implements EventSubscriberInterface
{
    public function __construct(private WebhookDispatcher $dispatcher)
    {
    }

    public function onUserCreated(UserEvent $event): void
    {
        $this->dispatcher->dispatchUserEvent($event->getUser(), WebhookEvent::USER_CREATED);
    }

    public function onUserDeleted(UserEvent $event): void
    {
        $this->dispatcher->dispatchUserEvent($event->getUser(), WebhookEvent::USER_DELETED);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::USER_CREATED => 'onUserCreated',
            UserEvent::USER_DELETED => 'onUserDeleted',
        ];
    }
}
