<?php

namespace App\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Class TwigGlobalListener.
 */
class TwigGlobalListener implements EventSubscriberInterface
{
    // https://stackoverflow.com/questions/54117732/how-to-define-global-variables-for-twig-templates-with-values-coming-from-the-db

    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var ObjectManager
     */
    private $manager;

    public function __construct(Environment $twig, ObjectManager $manager)
    {
        $this->twig = $twig;
        $this->manager = $manager;
    }

    public function injectGlobalVariables(ControllerEvent $event)
    {
        $domain = $this->manager->getRepository('App:Domain')->getDefaultDomain();
        if (null !== $domain) {
            $this->twig->addGlobal('domain', $domain->getName());
        } else {
            $this->twig->addGlobal('domain', 'defaultdomain');
        }
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::CONTROLLER => 'injectGlobalVariables'];
    }
}
