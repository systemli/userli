<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Domain;
use App\Event\DomainCreatedEvent;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Service\DomainManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DomainManagerTest extends TestCase
{
    private DomainRepository&Stub $domainRepository;
    private UserRepository&Stub $userRepository;
    private AliasRepository&Stub $aliasRepository;
    private EntityManagerInterface&Stub $entityManager;
    private EventDispatcherInterface&Stub $eventDispatcher;

    protected function setUp(): void
    {
        $this->domainRepository = $this->createStub(DomainRepository::class);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->aliasRepository = $this->createStub(AliasRepository::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createStub(EventDispatcherInterface::class);
    }

    public function testCreate(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Domain::class));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(DomainCreatedEvent::class),
                $this->equalTo(DomainCreatedEvent::NAME),
            );

        $manager = new DomainManager(
            $entityManager,
            $this->domainRepository,
            $this->userRepository,
            $this->aliasRepository,
            $eventDispatcher,
        );
        $result = $manager->create('example.org');

        self::assertInstanceOf(Domain::class, $result);
        self::assertEquals('example.org', $result->getName());
    }

    public function testGetDomainStats(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('countDomainUsers')
            ->with($domain)
            ->willReturn(10);
        $userRepository
            ->expects($this->once())
            ->method('countDomainAdmins')
            ->with($domain)
            ->willReturn(2);

        $aliasRepository = $this->createMock(AliasRepository::class);
        $aliasRepository
            ->expects($this->once())
            ->method('countDomainAliases')
            ->with($domain)
            ->willReturn(5);

        $manager = new DomainManager(
            $this->entityManager,
            $this->domainRepository,
            $userRepository,
            $aliasRepository,
            $this->eventDispatcher,
        );
        $result = $manager->getDomainStats($domain);

        self::assertEquals(10, $result['users']);
        self::assertEquals(5, $result['aliases']);
        self::assertEquals(2, $result['admins']);
    }
}
