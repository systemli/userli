<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Event\RandomAliasCreatedEvent;
use App\EventListener\RandomAliasCreationListener;
use App\Repository\AliasRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class RandomAliasCreationListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = RandomAliasCreationListener::getSubscribedEvents();

        self::assertArrayHasKey(RandomAliasCreatedEvent::NAME, $events);
        self::assertEquals('onRandomAliasCreated', $events[RandomAliasCreatedEvent::NAME]);
    }

    public function testOnRandomAliasCreatedDoesNothingWhenNoCollision(): void
    {
        $alias = new Alias();
        $alias->setSource('random123@example.org');

        $repository = $this->createStub(AliasRepository::class);
        $repository->method('findOneBySource')->willReturn(null);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $listener = new RandomAliasCreationListener($manager);
        $listener->onRandomAliasCreated(new RandomAliasCreatedEvent($alias));

        self::assertSame('random123@example.org', $alias->getSource());
    }

    public function testOnRandomAliasCreatedRegeneratesSourceOnCollision(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $alias = new Alias();
        $alias->setSource('collision@example.org');
        $alias->setDomain($domain);

        $existingAlias = new Alias();
        $callCount = 0;

        $repository = $this->createStub(AliasRepository::class);
        $repository->method('findOneBySource')->willReturnCallback(
            static function () use (&$callCount, $existingAlias) {
                ++$callCount;

                // First call returns existing alias (collision), second returns null (no collision)
                return $callCount === 1 ? $existingAlias : null;
            }
        );

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $listener = new RandomAliasCreationListener($manager);
        $listener->onRandomAliasCreated(new RandomAliasCreatedEvent($alias));

        // Source should have been regenerated
        self::assertNotSame('collision@example.org', $alias->getSource());
        self::assertStringEndsWith('@example.org', $alias->getSource());
    }
}
