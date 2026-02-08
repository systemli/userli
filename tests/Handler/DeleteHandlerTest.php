<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\Alias;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Entity\Voucher;
use App\Handler\DeleteHandler;
use App\Handler\WkdHandler;
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
    private EntityManagerInterface $entityManager;
    private array $removedEntities = [];

    protected function createHandler(array $aliases = [], array $vouchers = [], array $notifications = []): DeleteHandler
    {
        $passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()->getMock();
        $passwordUpdater->method('updatePassword')->willReturnCallback(static function (User $user): void {
            $user->setPassword('new_password');
        });

        $aliasRepository = $this->getMockBuilder(AliasRepository::class)
            ->disableOriginalConstructor()->getMock();
        $aliasRepository->method('findByUser')->willReturn($aliases);

        $voucherRepository = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()->getMock();
        $voucherRepository->method('findByUser')->willReturn($vouchers);

        $notificationRepository = $this->getMockBuilder(UserNotificationRepository::class)
            ->disableOriginalConstructor()->getMock();
        $notificationRepository->method('findByUser')->willReturn($notifications);

        $this->removedEntities = [];
        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->entityManager->method('getRepository')->willReturnCallback(
            static function (string $class) use ($aliasRepository, $voucherRepository, $notificationRepository) {
                return match ($class) {
                    Alias::class => $aliasRepository,
                    Voucher::class => $voucherRepository,
                    UserNotification::class => $notificationRepository,
                    default => throw new InvalidArgumentException("Unknown repository: $class"),
                };
            }
        );

        $this->entityManager->method('remove')->willReturnCallback(function ($entity): void {
            $this->removedEntities[] = $entity;
        });

        $wkdHandler = $this->getMockBuilder(WkdHandler::class)
            ->disableOriginalConstructor()->getMock();

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()->getMock();

        return new DeleteHandler($passwordUpdater, $this->entityManager, $wkdHandler, $eventDispatcher);
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

        $handler = $this->createHandler([$alias1, $alias2]);

        $handler->deleteUser($user);

        self::assertTrue($alias1->isDeleted());
        self::assertTrue($alias2->isDeleted());
    }

    public function testDeleteUserRemovesNotifications(): void
    {
        $user = new User('alice@example.org');

        $notification1 = $this->createMock(UserNotification::class);
        $notification2 = $this->createMock(UserNotification::class);

        $handler = $this->createHandler([], [], [$notification1, $notification2]);

        $handler->deleteUser($user);

        self::assertCount(2, $this->removedEntities);
        self::assertContains($notification1, $this->removedEntities);
        self::assertContains($notification2, $this->removedEntities);
    }
}
