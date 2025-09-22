<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class LocaleListener implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(param: 'locale')]
        private string $defaultLocale,
        #[Autowire(param: 'supported_locales')]
        private array $supportedLocales,
    ) {
    }

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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        // must be registered before (i.e. with a higher priority than) the default Locale listener
        return [KernelEvents::REQUEST => [['onKernelRequest', 20]]];
    }
}
