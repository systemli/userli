<?php

namespace App\EventListener;

use App\Event\Events;
use App\Event\UserEvent;
use App\Sender\RecoveryProcessMessageSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author doobry <doobry@systemli.org>
 */
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
        return array(
            Events::RECOVERY_PROCESS_STARTED => 'onRecoveryProcessStarted',
        );
    }

    /**
     * @param UserEvent $event
     */
    public function onRecoveryProcessStarted(UserEvent $event)
    {
        if (!$this->sendMail) {
            return;
        }

        $user = $event->getUser();
        $locale = $this->request->getCurrentRequest()->getLocale();

        $this->sender->send($user, $locale);
    }
}
