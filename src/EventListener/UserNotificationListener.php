<?php

declare(strict_types=1);

namespace App\EventListener;

use Exception;
use App\Event\UserNotificationEvent;
use App\Sender\CompromisedPasswordMessageSender;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class UserNotificationListener implements EventSubscriberInterface
{
    public function __construct(
        private CompromisedPasswordMessageSender $sender,
        private LoggerInterface                  $logger
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserNotificationEvent::COMPROMISED_PASSWORD => 'onCompromisedPassword',
        ];
    }

    public function onCompromisedPassword(UserNotificationEvent $event): void
    {
        try {
            $this->sender->send($event->getUser(), $event->getLocale());
        } catch (Exception $exception) {
            $this->logger->error('Failed to send compromised password notification', [
                'email' => $event->getUser()->getEmail(),
                'error' => $exception->getMessage()
            ]);
        }
    }
}
