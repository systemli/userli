<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\ReservedName;
use App\Entity\User;
use App\EntityListener\UpdatedTimeListener;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use stdClass;

class UpdatedTimeListenerTest extends TestCase
{
    private UpdatedTimeListener $listener;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->listener = new UpdatedTimeListener();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testPrePersistUpdatesUpdatedTimeForUser(): void
    {
        $user = new User('test@example.org');
        $oldDate = new DateTime('2020-01-01');
        $user->setUpdatedTime($oldDate);

        self::assertEquals('2020-01-01', $user->getUpdatedTime()->format('Y-m-d'));

        $args = new PrePersistEventArgs($user, $this->entityManager);
        $this->listener->prePersist($args);

        self::assertNotEquals('2020-01-01', $user->getUpdatedTime()->format('Y-m-d'));
        self::assertEquals((new DateTime())->format('Y-m-d'), $user->getUpdatedTime()->format('Y-m-d'));
    }

    public function testPreUpdateUpdatesUpdatedTimeForUser(): void
    {
        $user = new User('test@example.org');
        $oldDate = new DateTime('2020-01-01');
        $user->setUpdatedTime($oldDate);

        self::assertEquals('2020-01-01', $user->getUpdatedTime()->format('Y-m-d'));

        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->method('getObject')->willReturn($user);
        $this->listener->preUpdate($args);

        self::assertNotEquals('2020-01-01', $user->getUpdatedTime()->format('Y-m-d'));
        self::assertEquals((new DateTime())->format('Y-m-d'), $user->getUpdatedTime()->format('Y-m-d'));
    }

    public function testPrePersistUpdatesUpdatedTimeForAlias(): void
    {
        $alias = new Alias();
        $oldDate = new DateTime('2020-01-01');
        $alias->setUpdatedTime($oldDate);

        $args = new PrePersistEventArgs($alias, $this->entityManager);
        $this->listener->prePersist($args);

        self::assertEquals((new DateTime())->format('Y-m-d'), $alias->getUpdatedTime()->format('Y-m-d'));
    }

    public function testPrePersistUpdatesUpdatedTimeForDomain(): void
    {
        $domain = new Domain();
        $oldDate = new DateTime('2020-01-01');
        $domain->setUpdatedTime($oldDate);

        $args = new PrePersistEventArgs($domain, $this->entityManager);
        $this->listener->prePersist($args);

        self::assertEquals((new DateTime())->format('Y-m-d'), $domain->getUpdatedTime()->format('Y-m-d'));
    }

    public function testPrePersistUpdatesUpdatedTimeForReservedName(): void
    {
        $reservedName = new ReservedName();
        $oldDate = new DateTime('2020-01-01');
        $reservedName->setUpdatedTime($oldDate);

        $args = new PrePersistEventArgs($reservedName, $this->entityManager);
        $this->listener->prePersist($args);

        self::assertEquals((new DateTime())->format('Y-m-d'), $reservedName->getUpdatedTime()->format('Y-m-d'));
    }

    public function testPrePersistIgnoresNonUpdatedTimeEntities(): void
    {
        $entity = new stdClass();

        $args = new PrePersistEventArgs($entity, $this->entityManager);
        $this->listener->prePersist($args);

        // No exception thrown, entity is simply ignored
        self::assertTrue(true);
    }
}
