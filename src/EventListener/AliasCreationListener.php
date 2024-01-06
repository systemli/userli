<?php

namespace App\EventListener;

use App\Event\AliasCreatedEvent;
use App\Sender\AliasCreatedMessageSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AliasCreationListener implements EventSubscriberInterface
{
    /**
     * AliasCreationListener constructor.
     */
    public function __construct(private RequestStack $request, private AliasCreatedMessageSender $sender, private bool $sendMail)
    {
    }

    /**
     * @throws \Exception
     */
    public function onAliasCreated(AliasCreatedEvent $event): void
    {
        if (!$this->sendMail) {
            return;
        }

        if (null === $alias = $event->getAlias()) {
            throw new \Exception('Alias should not be null');
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
    public static function getSubscribedEvents(): array
    {
        return [
            AliasCreatedEvent::NAME => 'onAliasCreated',
        ];
    }
}
