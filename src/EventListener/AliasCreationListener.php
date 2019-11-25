<?php

namespace App\EventListener;

use App\Event\AliasCreatedEvent;
use App\Sender\AliasCreatedMessageSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AliasCreationListener implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $request;
    /**
     * @var AliasCreatedMessageSender
     */
    private $sender;
    /**
     * @var bool
     */
    private $sendMail;

    /**
     * AliasCreationListener constructor.
     */
    public function __construct(
        RequestStack $request,
        AliasCreatedMessageSender $sender,
        bool $sendMail
    ) {
        $this->request = $request;
        $this->sender = $sender;
        $this->sendMail = $sendMail;
    }

    /**
     * @throws \Exception
     */
    public function onAliasCreated(AliasCreatedEvent $event)
    {
        if (!$this->sendMail) {
            return;
        }

        if (null === $alias = $event->getAlias()) {
            throw new \Exception('User should not be null');
        }

        if (null === $user = $alias->getUser()) {
            throw new \Exception('User should not be null');
        }
        $locale = $this->request->getCurrentRequest()->getLocale();
        $this->sender->send($user, $alias, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AliasCreatedEvent::NAME => 'onAliasCreated',
        ];
    }
}
