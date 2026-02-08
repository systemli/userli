<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\UserEvent;
use App\Sender\RecoveryProcessMessageSender;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RecoveryProcessListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $request,
        private RecoveryProcessMessageSender $sender,
        #[Autowire('kernel.default_locale')]
        private string $defaultLocale,
    ) {
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::RECOVERY_PROCESS_STARTED => 'onRecoveryProcessStarted',
        ];
    }

    public function onRecoveryProcessStarted(UserEvent $event): void
    {
        $user = $event->getUser();
        $locale = $this->request->getSession()->get('_locale', $this->defaultLocale);

        $this->sender->send($user, $locale);
    }
}
