<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Event\DomainCreatedEvent;
use App\EventListener\DomainCreationListener;
use App\Handler\WkdHandler;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DomainCreationListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = DomainCreationListener::getSubscribedEvents();

        self::assertArrayHasKey(DomainCreatedEvent::NAME, $events);
        self::assertEquals('onDomainCreated', $events[DomainCreatedEvent::NAME]);
    }

    public function testOnDomainCreatedCreatesPostmasterAliasForNonDefaultDomain(): void
    {
        $defaultDomain = new Domain();
        $defaultDomain->setName('default.org');

        $newDomain = new Domain();
        $newDomain->setName('new.org');

        $repository = $this->createStub(DomainRepository::class);
        $repository->method('getDefaultDomain')->willReturn($defaultDomain);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $manager->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function ($alias) {
                return $alias instanceof Alias
                    && $alias->getSource() === 'postmaster@new.org'
                    && $alias->getDestination() === 'postmaster@default.org';
            }));
        $manager->expects($this->once())->method('flush');

        $wkdHandler = $this->createMock(WkdHandler::class);
        $wkdHandler->expects($this->once())
            ->method('getDomainWkdPath')
            ->with('new.org');

        $listener = new DomainCreationListener($manager, $wkdHandler);
        $listener->onDomainCreated(new DomainCreatedEvent($newDomain));
    }

    public function testOnDomainCreatedSkipsPostmasterAliasForDefaultDomain(): void
    {
        $defaultDomain = new Domain();
        $defaultDomain->setName('default.org');

        $repository = $this->createStub(DomainRepository::class);
        $repository->method('getDefaultDomain')->willReturn($defaultDomain);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $manager->expects($this->never())->method('persist');
        $manager->expects($this->never())->method('flush');

        $wkdHandler = $this->createMock(WkdHandler::class);
        $wkdHandler->expects($this->once())
            ->method('getDomainWkdPath')
            ->with('default.org');

        $listener = new DomainCreationListener($manager, $wkdHandler);
        $listener->onDomainCreated(new DomainCreatedEvent($defaultDomain));
    }
}
