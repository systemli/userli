<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\UserEvent;
use App\Message\RecoveryTokenCreatedMail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class RecoveryTokenCreatedListener
{
    public function __construct(
        private RequestStack $request,
        private MessageBusInterface $bus,
        #[Autowire(param: 'kernel.default_locale')]
        private string $defaultLocale,
    ) {
    }

    #[AsEventListener(event: UserEvent::RECOVERY_TOKEN_CREATED)]
    public function onRecoveryTokenCreated(UserEvent $event): void
    {
        $locale = $this->request->getSession()->get('_locale', $this->defaultLocale);

        $this->bus->dispatch(new RecoveryTokenCreatedMail($event->getUser()->getEmail(), $locale));
    }
}
