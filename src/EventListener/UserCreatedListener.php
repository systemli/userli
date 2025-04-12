<?php

namespace App\EventListener;

use App\Event\UserCreatedEvent;
use App\Handler\WebhookHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class UserCreatedListener implements EventSubscriberInterface
{
    public function __construct(
        private WebhookHandler $webhookHandler,
    ) {
    }

    public function onUserCreated(UserCreatedEvent $event): void
    {
        $this->webhookHandler->send($event->getUser(), 'user.created');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::class => 'onUserCreated',
        ];
    }
}
