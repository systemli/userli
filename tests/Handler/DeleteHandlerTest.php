<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Entity\Voucher;
use App\Event\AliasDeletedEvent;
use App\Event\UserEvent;
use App\Handler\DeleteHandler;
use App\Helper\PasswordUpdater;
use App\Repository\AliasRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class DeleteHandlerTest extends TestCase
{
    private array $removedEntities = [];
    private array $dispatchedEvents = [];

    protected function createHandler(array $aliases = [], array $vouchers = [], array $notifications = []): DeleteHandler
    {
        $passwordUpdater = $this->createStub(PasswordUpdater::class);
        $passwordUpdater->method('updatePassword')->willReturnCallback(static function (User $user): void {
            $user->setPassword('new_password');
        });

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findByUser')->willReturn($aliases);
        $aliasRepository->method('findByUserAcrossDomains')->willReturn($aliases);

        $voucherRepository = $this->createStub(VoucherRepository::class);
        $voucherRepository->method('findByUser')->willReturn($vouchers);

        $notificationRepository = $this->createStub(UserNotificationRepository::class);
        $notificationRepository->method('findByUser')->willReturn($notifications);

        $this->removedEntities = [];
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            static fn (string $class) => match ($class) {
                Alias::class => $aliasRepository,
                Voucher::class => $voucherRepository,
                UserNotification::class => $notificationRepository,
                default => throw new InvalidArgumentException("Unknown repository: $class"),
            }
        );

        $entityManager->method('remove')->willReturnCallback(function ($entity): void {
            $this->removedEntities[] = $entity;
        });

        $this->dispatchedEvents = [];
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(function (object $event, string $eventName) {
            $this->dispatchedEvents[] = ['event' => $event, 'name' => $eventName];

            return $event;
        });

        return new DeleteHandler(
            $passwordUpdater,
            $entityManager,
            $eventDispatcher,
        );
    }

    public function testDeleteAlias(): void
    {
        $handler = $this->createHandler();

        $user = new User('alice@example.org');
        $alias = new Alias();
        $alias->setUser($user);

        $user2 = new User('bob@example.org');
        $handler->deleteAlias($alias, $user2);

        self::assertNotTrue($alias->isDeleted());
        self::assertEquals($alias->getUser(), $user);

        $handler->deleteAlias($alias);

        self::assertTrue($alias->isDeleted());
        self::assertNotEquals($alias->getUser(), $user);
        self::assertNull($alias->getDestination());
    }

    public function testDeleteAliasDispatchesEvent(): void
    {
        $handler = $this->createHandler();

        $user = new User('alice@example.org');
        $alias = new Alias();
        $alias->setUser($user);
        $alias->setSource('alias@example.org');

        $handler->deleteAlias($alias);

        self::assertCount(1, $this->dispatchedEvents);
        self::assertSame(AliasDeletedEvent::CUSTOM, $this->dispatchedEvents[0]['name']);
        self::assertInstanceOf(AliasDeletedEvent::class, $this->dispatchedEvents[0]['event']);
        self::assertSame($alias, $this->dispatchedEvents[0]['event']->getAlias());
    }

    public function testDeleteAliasDoesNotDispatchEventWhenUserMismatch(): void
    {
        $handler = $this->createHandler();

        $user = new User('alice@example.org');
        $alias = new Alias();
        $alias->setUser($user);

        $otherUser = new User('bob@example.org');
        $handler->deleteAlias($alias, $otherUser);

        self::assertCount(0, $this->dispatchedEvents);
    }

    public function testDeleteUser(): void
    {
        $handler = $this->createHandler();

        $oldPassword = 'old_password';

        $user = new User('alice@example.org');
        $user->setPassword($oldPassword);

        $handler->deleteUser($user);

        self::assertTrue($user->isDeleted());
        self::assertNotEquals($oldPassword, $user->getPassword());
    }

    public function testDeleteUserRemovesVouchers(): void
    {
        $user = new User('alice@example.org');

        $voucher1 = new Voucher('CODE1');
        $voucher1->setUser($user);

        $voucher2 = new Voucher('CODE2');
        $voucher2->setUser($user);

        $handler = $this->createHandler([], [$voucher1, $voucher2]);

        $handler->deleteUser($user);

        self::assertCount(2, $this->removedEntities);
        self::assertContains($voucher1, $this->removedEntities);
        self::assertContains($voucher2, $this->removedEntities);
    }

    public function testDeleteUserDeletesAliases(): void
    {
        $user = new User('alice@example.org');

        $alias1 = new Alias();
        $alias1->setUser($user);
        $alias1->setSource('alias1@example.org');

        $alias2 = new Alias();
        $alias2->setUser($user);
        $alias2->setSource('alias2@example.org');

        $alias3 = new Alias();
        $alias3->setUser($user);
        $alias3->setSource('crossdomain@example.com');
        $crossDomain = new Domain();
        $crossDomain->setName('example.com');
        $alias3->setDomain($crossDomain);

        $handler = $this->createHandler([$alias1, $alias2, $alias3]);

        $handler->deleteUser($user);

        self::assertTrue($alias1->isDeleted());
        self::assertTrue($alias2->isDeleted());
        self::assertTrue($alias3->isDeleted());
    }

    public function testDeleteUserRemovesNotifications(): void
    {
        $user = new User('alice@example.org');

        $notification1 = $this->createStub(UserNotification::class);
        $notification2 = $this->createStub(UserNotification::class);

        $handler = $this->createHandler([], [], [$notification1, $notification2]);

        $handler->deleteUser($user);

        self::assertCount(2, $this->removedEntities);
        self::assertContains($notification1, $this->removedEntities);
        self::assertContains($notification2, $this->removedEntities);
    }

    public function testDeleteUserDispatchesAliasAndUserEvents(): void
    {
        $user = new User('alice@example.org');

        $alias1 = new Alias();
        $alias1->setUser($user);
        $alias1->setSource('alias1@example.org');

        $alias2 = new Alias();
        $alias2->setUser($user);
        $alias2->setSource('alias2@example.org');

        $handler = $this->createHandler([$alias1, $alias2]);

        $handler->deleteUser($user);

        // Expect 2 AliasDeletedEvents + 1 UserEvent::USER_DELETED
        self::assertCount(3, $this->dispatchedEvents);

        // Alias events come first
        self::assertSame(AliasDeletedEvent::CUSTOM, $this->dispatchedEvents[0]['name']);
        self::assertSame($alias1, $this->dispatchedEvents[0]['event']->getAlias());

        self::assertSame(AliasDeletedEvent::CUSTOM, $this->dispatchedEvents[1]['name']);
        self::assertSame($alias2, $this->dispatchedEvents[1]['event']->getAlias());

        // User event comes last
        self::assertSame(UserEvent::USER_DELETED, $this->dispatchedEvents[2]['name']);
        self::assertSame($user, $this->dispatchedEvents[2]['event']->getUser());
    }
}
