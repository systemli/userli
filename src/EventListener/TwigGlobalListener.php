<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Domain;
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
    public function __construct(private readonly Environment $twig, private readonly EntityManagerInterface $manager)
    {
    }

    public function injectGlobalVariables(ControllerEvent $event): void
    {
        $domain = $this->manager->getRepository(Domain::class)->getDefaultDomain();
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
