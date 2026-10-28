<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\UserEvent;
use App\Mail\RecoveryProcessMailer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RecoveryProcessListener
{
    public function __construct(
        private RequestStack $request,
        private RecoveryProcessMailer $mailer,
        #[Autowire('kernel.default_locale')]
        private string $defaultLocale,
    ) {
    }

    #[AsEventListener(event: UserEvent::RECOVERY_PROCESS_STARTED)]
    public function onRecoveryProcessStarted(UserEvent $event): void
    {
        $user = $event->getUser();
        $locale = $this->request->getSession()->get('_locale', $this->defaultLocale);

        $this->mailer->send($user, $locale);
    }
}
