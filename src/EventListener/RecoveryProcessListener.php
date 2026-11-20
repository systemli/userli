<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\UserEvent;
use App\Message\RecoveryProcessMail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class RecoveryProcessListener
{
    public function __construct(
        private RequestStack $request,
        private MessageBusInterface $bus,
        #[Autowire(param: 'kernel.default_locale')]
        private string $defaultLocale,
    ) {
    }

    #[AsEventListener(event: UserEvent::RECOVERY_PROCESS_STARTED)]
    public function onRecoveryProcessStarted(UserEvent $event): void
    {
        $locale = $this->request->getSession()->get('_locale', $this->defaultLocale);

        $this->bus->dispatch(new RecoveryProcessMail($event->getUser()->getEmail(), $locale));
    }
}
