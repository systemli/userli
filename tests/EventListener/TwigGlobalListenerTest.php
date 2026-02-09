<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Domain;
use App\EventListener\TwigGlobalListener;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class TwigGlobalListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = TwigGlobalListener::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::CONTROLLER, $events);
        self::assertEquals('injectGlobalVariables', $events[KernelEvents::CONTROLLER]);
    }

    public function testInjectGlobalVariablesSetsDomainName(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $repository = $this->createStub(DomainRepository::class);
        $repository->method('getDefaultDomain')->willReturn($domain);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('addGlobal')
            ->with('domain', 'example.org');

        $event = new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            static function (): void {},
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener = new TwigGlobalListener($twig, $manager);
        $listener->injectGlobalVariables($event);
    }

    public function testInjectGlobalVariablesFallsBackToDefaultDomain(): void
    {
        $repository = $this->createStub(DomainRepository::class);
        $repository->method('getDefaultDomain')->willReturn(null);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('addGlobal')
            ->with('domain', 'defaultdomain');

        $event = new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            static function (): void {},
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener = new TwigGlobalListener($twig, $manager);
        $listener->injectGlobalVariables($event);
    }
}
