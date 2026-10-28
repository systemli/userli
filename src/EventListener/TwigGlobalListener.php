<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Class TwigGlobalListener.
 */
final readonly class TwigGlobalListener
{
    public function __construct(private Environment $twig, private EntityManagerInterface $manager)
    {
    }

    #[AsEventListener(event: KernelEvents::CONTROLLER)]
    public function injectGlobalVariables(ControllerEvent $event): void
    {
        $domain = $this->manager->getRepository(Domain::class)->getDefaultDomain();
        if (null !== $domain) {
            $this->twig->addGlobal('domain', $domain->getName());
        } else {
            $this->twig->addGlobal('domain', 'defaultdomain');
        }
    }
}
