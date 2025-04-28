<?php

namespace App\EventListener;

use App\Event\UserDeletedEvent;
use App\Handler\WebhookHandler;
use App\Handler\WkdHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class UserDeletionListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly WebhookHandler $webhookHandler,
        private readonly WkdHandler $wkdHandler
    ) {
    }

    public function sendWebhook(UserDeletedEvent $event): void
    {
        $this->webhookHandler->send($event->getUser(), 'user.deleted');
    }

    public function deletePgpKey(UserDeletedEvent $event): void
    {
        $this->wkdHandler->deleteKey($event->getUser->getEmail());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserDeletedEvent::class => [
            'sendWebhook',
            'deletePgpKey'
            ],
        ];
    }


}
