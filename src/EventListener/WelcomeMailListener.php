<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Message\WelcomeMail;
use App\Entity\User;
use App\Event\UserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class WelcomeMailListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack        $request,
        private MessageBusInterface $bus,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::USER_CREATED => 'onUserCreated',
        ];
    }

    public function onUserCreated(UserEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $locale = $this->request->getSession()->get('_locale');

        $this->bus->dispatch(new WelcomeMail($user->getEmail(), $locale));
    }
}
