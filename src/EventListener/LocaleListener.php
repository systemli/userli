<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

        if (null === $request->attributes->get('_locale')) {
            if (null !== $locale = $request->getPreferredLanguage($this->supportedLocales)) {
                $request->attributes->set('_locale', $locale);
            }
        }
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
