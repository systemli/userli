<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

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
     * @var UrlMatcherInterface
     */
    private $urlMatcher;

    /**
     * LocaleListener constructor.
     *
     * @param string[]            $supportedLocales
     * @param UrlMatcherInterface $urlMatcher
     */
    public function __construct(array $supportedLocales, UrlMatcherInterface $urlMatcher)
    {
        $this->supportedLocales = $supportedLocales;
        $this->urlMatcher = $urlMatcher;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $route = $request->getRequestUri();

        // Check if we are already on localized path
        if (null !== $this->checkLanguage($route)) {
            return;
        }

        // Make sure _locale is set
        if (null === $request->attributes->get('_locale')) {
            if (null !== $locale = $request->getPreferredLanguage($this->supportedLocales)) {
                $request->attributes->set('_locale', $locale);
            }
        }

        // redirect if localized version exists
        $newLocale = $request->attributes->get('_locale');
        $newRoute = '/'.$newLocale.$route;
        try {
            $this->urlMatcher->match($newRoute);
            $event->setResponse(new RedirectResponse($newRoute));
        } catch (ResourceNotFoundException $e) {
            // ignore errors, we just redirect if there was none
        } catch (MethodNotAllowedException $e) {
            // ignore errors, we just redirect if there was none
        }
    }

    /**
     * @param $route
     *
     * @return string|null
     */
    private function checkLanguage($route)
    {
        foreach ($this->supportedLocales as $locale) {
            if (preg_match_all("/^\/$locale\//", $route)) {
                return $locale;
            }
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
