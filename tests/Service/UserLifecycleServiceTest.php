<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Alias;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Entity\Voucher;
use App\Enum\UserNotificationType;
use App\Event\AliasDeletedEvent;
use App\Event\UserEvent;
use App\Helper\PasswordUpdater;
use App\Repository\AliasRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\VoucherRepository;
use App\Service\MailCryptCredentialRotation;
use App\Service\UserLifecycleService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserLifecycleServiceTest extends TestCase
{
    private EntityManagerInterface&Stub $manager;
    private PasswordUpdater&Stub $passwordUpdater;
    private MailCryptCredentialRotation&Stub $mailCryptCredentialRotation;
    private EventDispatcherInterface&Stub $eventDispatcher;

    protected function setUp(): void
    {
        $this->manager = $this->createStub(EntityManagerInterface::class);
        $this->passwordUpdater = $this->createStub(PasswordUpdater::class);
        $this->mailCryptCredentialRotation = $this->createStub(MailCryptCredentialRotation::class);
        $this->eventDispatcher = $this->createStub(EventDispatcherInterface::class);
    }

    private function createService(): UserLifecycleService
    {
        return new UserLifecycleService(
            $this->manager,
            $this->passwordUpdater,
            $this->mailCryptCredentialRotation,
            $this->eventDispatcher,
        );
    }

    // --- reset() ---

    public function testResetUpdatesPassword(): void
    {
        $user = new User('user@example.org');

        /** @var PasswordUpdater&MockObject $passwordUpdater */
        $passwordUpdater = $this->createMock(PasswordUpdater::class);
        $passwordUpdater->expects(self::once())->method('updatePassword')->with($user, 'newpass');

        $service = new UserLifecycleService(
            $this->manager,
            $passwordUpdater,
            $this->mailCryptCredentialRotation,
            $this->eventDispatcher,
        );

        $service->reset($user, 'newpass');
    }

    public function testResetCallsMailCryptRotationAndReturnsToken(): void
    {
        $user = new User('user@example.org');

        /** @var MailCryptCredentialRotation&MockObject $rotation */
        $rotation = $this->createMock(MailCryptCredentialRotation::class);
        $rotation->expects(self::once())
            ->method('rotate')
            ->with($user, 'pass')
            ->willReturn('recovery-token');

        $service = new UserLifecycleService(
            $this->manager,
            $this->passwordUpdater,
            $rotation,
            $this->eventDispatcher,
        );

        self::assertSame('recovery-token', $service->reset($user, 'pass'));
    }

    public function testResetReturnsNullWhenMailCryptDisabled(): void
    {
        $user = new User('user@example.org');

        $this->mailCryptCredentialRotation->method('rotate')->willReturn(null);

        self::assertNull($this->createService()->reset($user, 'pass'));
    }

    public function testResetClearsTotpSettings(): void
    {
        $user = new User('user@example.org');
        $user->setTotpConfirmed(true);
        $user->setTotpSecret('secret');
        $user->setTotpBackupCodes(['code1', 'code2']);

        $this->createService()->reset($user, 'pass');

        self::assertFalse($user->getTotpConfirmed());
        self::assertNull($user->getTotpSecret());
        self::assertEmpty($user->getTotpBackupCodes());
    }

    public function testResetFlushes(): void
    {
        $user = new User('user@example.org');

        /** @var EntityManagerInterface&MockObject $manager */
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('flush');

        $service = new UserLifecycleService(
            $manager,
            $this->passwordUpdater,
            $this->mailCryptCredentialRotation,
            $this->eventDispatcher,
        );

        $service->reset($user, 'pass');
    }

    public function testResetDispatchesUserResetEventWhenNotDeleted(): void
    {
        $user = new User('user@example.org');

        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(UserEvent::class), UserEvent::USER_RESET);

        $service = new UserLifecycleService(
            $this->manager,
            $this->passwordUpdater,
            $this->mailCryptCredentialRotation,
            $eventDispatcher,
        );

        $service->reset($user, 'pass');
    }

    public function testResetDoesNotDispatchEventForDeletedUser(): void
    {
        $user = new User('user@example.org');
        $user->setDeleted(true);

        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::never())->method('dispatch');

        $service = new UserLifecycleService(
            $this->manager,
            $this->passwordUpdater,
            $this->mailCryptCredentialRotation,
            $eventDispatcher,
        );

        $service->reset($user, 'pass');
    }

    // --- delete() ---

    public function testDeleteSoftDeletesAliasesAndCascadesVouchersAndNotifications(): void
    {
        $user = new User('user@example.org');

        $randomAlias = new Alias();
        $randomAlias->setRandom(true);

        $customAlias = new Alias();
        $customAlias->setSource('alias@example.org');

        $redeemedVoucher = new Voucher('redeemed-code');
        $redeemedVoucher->setRedeemedTime(new DateTimeImmutable());

        $unredeemedVoucher = new Voucher('unredeemed-code');

        $notification = new UserNotification($user, UserNotificationType::PASSWORD_COMPROMISED);

        $aliasRepo = $this->createStub(AliasRepository::class);
        $voucherRepo = $this->createStub(VoucherRepository::class);
        $notificationRepo = $this->createStub(UserNotificationRepository::class);

        $aliasRepo->method('findByUserAcrossDomains')
            ->willReturnOnConsecutiveCalls(
                [$randomAlias, $customAlias],
                [$customAlias],
            );
        $voucherRepo->method('findByUser')->willReturn([$redeemedVoucher, $unredeemedVoucher]);
        $notificationRepo->method('findByUser')->willReturn([$notification]);

        /** @var EntityManagerInterface&MockObject $manager */
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [Alias::class, $aliasRepo],
            [Voucher::class, $voucherRepo],
            [UserNotification::class, $notificationRepo],
        ]);
        $manager->expects(self::exactly(2))->method('remove');
        $manager->expects(self::once())->method('flush');

        $dispatchedEvents = [];

        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event, string $eventName) use (&$dispatchedEvents): object {
                $dispatchedEvents[] = [$event, $eventName];

                return $event;
            });

        $service = new UserLifecycleService($manager, $this->passwordUpdater, $this->mailCryptCredentialRotation, $eventDispatcher);
        $service->delete($user);

        self::assertTrue($randomAlias->isDeleted());
        self::assertTrue($customAlias->isDeleted());
        self::assertTrue($user->isDeleted());
        self::assertInstanceOf(AliasDeletedEvent::class, $dispatchedEvents[0][0]);
        self::assertSame(AliasDeletedEvent::CUSTOM, $dispatchedEvents[0][1]);
        self::assertInstanceOf(UserEvent::class, $dispatchedEvents[1][0]);
        self::assertSame(UserEvent::USER_DELETED, $dispatchedEvents[1][1]);
    }

    public function testDeleteDispatchesUserDeletedEvent(): void
    {
        $user = new User('user@example.org');

        $aliasRepo = $this->createStub(AliasRepository::class);
        $aliasRepo->method('findByUserAcrossDomains')->willReturn([]);

        $voucherRepo = $this->createStub(VoucherRepository::class);
        $voucherRepo->method('findByUser')->willReturn([]);

        $notificationRepo = $this->createStub(UserNotificationRepository::class);
        $notificationRepo->method('findByUser')->willReturn([]);

        /** @var EntityManagerInterface&MockObject $manager */
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [Alias::class, $aliasRepo],
            [Voucher::class, $voucherRepo],
            [UserNotification::class, $notificationRepo],
        ]);

        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(UserEvent::class), UserEvent::USER_DELETED);

        $service = new UserLifecycleService($manager, $this->passwordUpdater, $this->mailCryptCredentialRotation, $eventDispatcher);
        $service->delete($user);
    }

    // --- restore() ---

    public function testRestoreResetsCredentialsAndClearsDeletedFlag(): void
    {
        $user = new User('user@example.org');
        $user->setDeleted(true);

        $this->mailCryptCredentialRotation->method('rotate')->willReturn('token');

        /** @var EntityManagerInterface&MockObject $manager */
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::exactly(2))->method('flush');

        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(UserEvent::class), UserEvent::USER_RESTORED);

        $service = new UserLifecycleService($manager, $this->passwordUpdater, $this->mailCryptCredentialRotation, $eventDispatcher);

        $result = $service->restore($user, 'newpass');

        self::assertFalse($user->isDeleted());
        self::assertSame('token', $result);
    }

    public function testRestoreDoesNotDispatchUserResetEvent(): void
    {
        $user = new User('user@example.org');
        $user->setDeleted(true);

        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(UserEvent::class), UserEvent::USER_RESTORED);

        $service = new UserLifecycleService($this->manager, $this->passwordUpdater, $this->mailCryptCredentialRotation, $eventDispatcher);
        $service->restore($user, 'pass');
    }
}
