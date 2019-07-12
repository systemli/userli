<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author louis <louis@systemli.org>
 */
class LocaleListener implements EventSubscriberInterface
{
    /**
     * @var string[]
     */
    private $supportedLocales;

    /**
     * LocaleListener constructor.
     * @param string[] $supportedLocales
     */
    public function __construct(array $supportedLocales)
    {
        $this->supportedLocales = $supportedLocales;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $route = $request->getRequestUri();

        // Check if we are already on localized path
        if (null !== $this->checkLanguage($route)) return;

        // Make sure _locale is set
        if (null === $request->attributes->get('_locale')) {
            if (null !== $locale = $request->getPreferredLanguage($this->supportedLocales)) {
                $request->attributes->set('_locale', $locale);
            }
        }

        // Don't redirect specific urls
        if ("/login_check" === $route) return;
        if (strpos($route, 'logout') !== false) return;
        if (strpos($route, '/admin/') !== false) return;

        // redirect
        $newLocale = $request->attributes->get('_locale');
        $newRoute = '/'.$newLocale.$route;
        $event->setResponse(new RedirectResponse(($newRoute)));
    }

    /**
     * @param $route
     * @return null|string
     */
    private function checkLanguage($route){
        foreach($this->supportedLocales as $locale){
            if(preg_match_all("/^\/$locale\//", $route))
                return $locale;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
        );
    }
}
