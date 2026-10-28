<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LocaleListener
{
    public function __construct(
        #[Autowire('kernel.default_locale')]
        private string $defaultLocale,
        #[Autowire(param: 'locales')]
        private array $supportedLocales,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->attributes->getBoolean('_stateless')) {
            return;
        }

        $session = $request->getSession();
        $sessionLocale = $session->get('_locale');

        if ($queryLocale = $request->query->get('_locale')) {
            // Set locale from query string if supported
            if (in_array($queryLocale, $this->supportedLocales, true)) {
                $sessionLocale = $queryLocale;
                $session->set('_locale', $sessionLocale);
            }
        }

        if (!$sessionLocale) {
            // Set locale from browser if supported, fall back to default locale
            $preferredLanguage = $request->getPreferredLanguage($this->supportedLocales);
            $sessionLocale = $preferredLanguage ?: $this->defaultLocale;
            $session->set('_locale', $sessionLocale);
        }

        $request->setLocale($sessionLocale);
    }
}
