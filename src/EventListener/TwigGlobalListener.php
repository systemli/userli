<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
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
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(Environment $twig, EntityManagerInterface $manager)
    {
        $this->twig = $twig;
        $this->manager = $manager;
    }

    public function injectGlobalVariables(ControllerEvent $event): void
    {
        $domain = $this->manager->getRepository('App:Domain')->getDefaultDomain();
        if (null !== $domain) {
            $this->twig->addGlobal('domain', $domain->getName());
        } else {
            $this->twig->addGlobal('domain', 'defaultdomain');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'injectGlobalVariables'];
    }
}
