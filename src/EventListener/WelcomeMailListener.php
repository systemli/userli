<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Event\UserEvent;
use App\Message\WelcomeMail;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class WelcomeMailListener
{
    public function __construct(
        private RequestStack $request,
        private MessageBusInterface $bus,
    ) {
    }

    #[AsEventListener(event: UserEvent::USER_CREATED)]
    #[AsEventListener(event: UserEvent::USER_RESTORED)]
    public function onUserCreated(UserEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $locale = $this->request->getCurrentRequest()?->getSession()->get('_locale') ?? 'en';

        $this->bus->dispatch(new WelcomeMail($user->getEmail(), $locale));
    }
}
