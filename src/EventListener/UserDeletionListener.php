<?php

namespace App\EventListener;

use App\Event\UserDeletedEvent;
use App\Handler\WebhookHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class UserDeletionListener implements EventSubscriberInterface
{
    public function __construct(
        private WebhookHandler $webhookHandler,
    ) {
    }

    public function onUserDeleted(UserDeletedEvent $event): void
    {
        $this->webhookHandler->send($event->getUser(), 'user.deleted');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserDeletedEvent::class => 'onUserDeleted',
        ];
    }
}
