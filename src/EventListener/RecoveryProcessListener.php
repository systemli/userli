<?php

namespace App\EventListener;

use App\Event\RecoveryProcessEvent;
use App\Event\UserEvent;
use App\Sender\RecoveryProcessMessageSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RecoveryProcessListener implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $request;
    /**
     * @var RecoveryProcessMessageSender
     */
    private $sender;
    /**
     * @var bool
     */
    private $sendMail;

    /**
     * RecoveryProcessListener constructor.
     *
     * @param RequestStack                 $request
     * @param RecoveryProcessMessageSender $sender
     * @param bool                         $sendMail
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
    public static function getSubscribedEvents()
    {
        return [
            RecoveryProcessEvent::NAME => 'onRecoveryProcessStarted',
        ];
    }

    /**
     * @param UserEvent $event
     *
     * @throws \Exception
     */
    public function onRecoveryProcessStarted(UserEvent $event)
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
