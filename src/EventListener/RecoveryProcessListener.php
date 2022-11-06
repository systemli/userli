<?php

namespace App\EventListener;

use App\Event\RecoveryProcessEvent;
use App\Event\UserEvent;
use App\Sender\RecoveryProcessMessageSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RecoveryProcessListener implements EventSubscriberInterface
{
    private RequestStack $request;
    private RecoveryProcessMessageSender $sender;
    private bool $sendMail;

    /**
     * RecoveryProcessListener constructor.
     */
    public function __construct(RequestStack $request, RecoveryProcessMessageSender $sender, bool $sendMail)
    {
        $this->request = $request;
        $this->sender = $sender;
        $this->sendMail = $sendMail;
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
     * @throws \Exception
     */
    public function onRecoveryProcessStarted(UserEvent $event): void
    {
        if (!$this->sendMail) {
            return;
        }

        if (null === $user = $event->getUser()) {
            throw new \Exception('User should not be null');
        }
        $locale = $this->request->getCurrentRequest()->getLocale();

        $this->sender->send($user, $locale);
    }
}
