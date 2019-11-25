<?php

namespace App\EventListener;

use App\Event\Events;
use App\Event\UserEvent;
use App\Sender\WelcomeMessageSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RegistrationListener implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $request;
    /**
     * @var WelcomeMessageSender
     */
    private $sender;
    /**
     * @var bool
     */
    private $sendMail;

    /**
     * Constructor.
     *
     * @param bool $sendMail
     */
    public function __construct(RequestStack $request, WelcomeMessageSender $sender, $sendMail)
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
            Events::MAIL_ACCOUNT_CREATED => 'onMailAccountCreated',
        ];
    }

    public function onMailAccountCreated(UserEvent $event)
    {
        if (!$this->sendMail) {
            return;
        }

        $user = $event->getUser();
        $locale = $this->request->getCurrentRequest()->getLocale();

        $this->sender->send($user, $locale);
    }
}
