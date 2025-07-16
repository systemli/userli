<?php

namespace App\EventListener;

use Exception;
use App\Event\RecoveryProcessEvent;
use App\Event\UserEvent;
use App\Sender\RecoveryProcessMessageSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class RecoveryProcessListener implements EventSubscriberInterface
{
    public function __construct(
        private RecoveryProcessMessageSender $sender,
        private bool                         $sendMail,
        private string                       $defaultLocale,
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RecoveryProcessEvent::NAME => 'onRecoveryProcessStarted',
        ];
    }

    /**
     * @throws Exception
     */
    public function onRecoveryProcessStarted(RecoveryProcessEvent $event): void
    {
        if (!$this->sendMail) {
            return;
        }

        if (null === $user = $event->getUser()) {
            throw new Exception('User should not be null');
        }

        $locale = $event->getLocale ?? $this->defaultLocale;

        $this->sender->send($user, $locale);
    }
}
