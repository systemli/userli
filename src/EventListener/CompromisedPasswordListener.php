<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use App\Service\PasswordCompromisedService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

readonly class CompromisedPasswordListener implements EventSubscriberInterface
{
    public function __construct(
        private PasswordCompromisedService $passwordCompromisedService,
        private RequestStack $requestStack,
        private string $defaultLocale = 'en',
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            LoginEvent::NAME => 'onLogin',
        ];
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $password = $event->getRequest()->get('_password');
        if ($password === null) {
            return;
        }

        $locale = $event->getRequest()->getLocale();

        $this->checkAndNotify($user, $password, $locale);
    }

    public function onLogin(LoginEvent $event): void
    {
        $plainPassword = $event->getPlainPassword();
        if ($plainPassword === null) {
            return;
        }

        if (!$event->getUser() instanceof User) {
            return;
        }

        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? $this->defaultLocale;

        $this->checkAndNotify($event->getUser(), $plainPassword, $locale);
    }

    private function checkAndNotify(User $user, string $plainPassword, string $locale): void
    {
        $this->passwordCompromisedService->checkAndNotify($user, $plainPassword, $locale);
    }
}
