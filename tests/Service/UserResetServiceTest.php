<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Event\UserEvent;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use App\Service\UserResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserResetServiceTest extends TestCase
{
    private EntityManagerInterface&Stub $manager;
    private PasswordUpdater&Stub $passwordUpdater;
    private MailCryptKeyHandler&Stub $mailCryptKeyHandler;
    private RecoveryTokenHandler&Stub $recoveryTokenHandler;
    private EventDispatcherInterface&Stub $eventDispatcher;

    protected function setUp(): void
    {
        $this->manager = $this->createStub(EntityManagerInterface::class);
        $this->passwordUpdater = $this->createStub(PasswordUpdater::class);
        $this->mailCryptKeyHandler = $this->createStub(MailCryptKeyHandler::class);
        $this->recoveryTokenHandler = $this->createStub(RecoveryTokenHandler::class);
        $this->eventDispatcher = $this->createStub(EventDispatcherInterface::class);
    }

    private function createService(int $mailCryptEnv): UserResetService
    {
        return new UserResetService(
            $this->manager,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->recoveryTokenHandler,
            $this->eventDispatcher,
            $mailCryptEnv
        );
    }

    public function testResetUserUpdatesPassword(): void
    {
        $user = new User('user@example.org');
        $password = 'newSecurePassword123';

        $passwordUpdater = $this->createMock(PasswordUpdater::class);
        $passwordUpdater
            ->expects($this->once())
            ->method('updatePassword')
            ->with($user, $password);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('flush');

        $service = new UserResetService(
            $manager,
            $passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->recoveryTokenHandler,
            $this->eventDispatcher,
            0
        );

        $service->resetUser($user, $password);
    }

    public function testResetUserClearsTotpSettings(): void
    {
        $service = $this->createService(0);
        $user = new User('user@example.org');
        $user->setTotpSecret('secret');
        $user->setTotpConfirmed(true);
        $user->setTotpBackupCodes(['123456', '654321']);

        $service->resetUser($user, 'password123');

        self::assertFalse($user->getTotpConfirmed());
        self::assertNull($user->getTotpSecret());
        self::assertEmpty($user->getTotpBackupCodes());
        self::assertFalse($user->isTotpAuthenticationEnabled());
    }

    public function testResetUserWithMailCryptRegeneratesKeysAndRecoveryToken(): void
    {
        $user = new User('user@example.org');
        $password = 'newSecurePassword123';

        $mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);
        $mailCryptKeyHandler
            ->expects($this->once())
            ->method('create')
            ->with($user, $password, true);

        $recoveryTokenHandler = $this->createMock(RecoveryTokenHandler::class);
        $recoveryTokenHandler
            ->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturnCallback(static function (User $user): void {
                $user->setPlainRecoveryToken('generated-recovery-token');
            });

        $service = new UserResetService(
            $this->manager,
            $this->passwordUpdater,
            $mailCryptKeyHandler,
            $recoveryTokenHandler,
            $this->eventDispatcher,
            2
        );

        $recoveryToken = $service->resetUser($user, $password);

        self::assertEquals('generated-recovery-token', $recoveryToken);
    }

    public function testResetUserWithoutMailCryptDoesNotRegenerateKeys(): void
    {
        $user = new User('user@example.org');
        $password = 'newSecurePassword123';

        $mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);
        $mailCryptKeyHandler
            ->expects($this->never())
            ->method('create');

        $recoveryTokenHandler = $this->createMock(RecoveryTokenHandler::class);
        $recoveryTokenHandler
            ->expects($this->never())
            ->method('create');

        $service = new UserResetService(
            $this->manager,
            $this->passwordUpdater,
            $mailCryptKeyHandler,
            $recoveryTokenHandler,
            $this->eventDispatcher,
            0
        );

        $recoveryToken = $service->resetUser($user, $password);

        self::assertNull($recoveryToken);
    }

    public function testResetUserErasesCredentials(): void
    {
        $service = $this->createService(0);
        $user = new User('user@example.org');
        $user->setPlainMailCryptPrivateKey('secretPrivateKey');
        $user->setPlainRecoveryToken('secretRecoveryToken');

        $service->resetUser($user, 'newPassword123');

        self::assertNull($user->getPlainMailCryptPrivateKey());
        self::assertNull($user->getPlainRecoveryToken());
    }

    public function testResetUserFlushesEntityManager(): void
    {
        $user = new User('user@example.org');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('flush');

        $service = new UserResetService(
            $manager,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->recoveryTokenHandler,
            $this->eventDispatcher,
            0
        );

        $service->resetUser($user, 'password123');
    }

    public function testResetUserDispatchesUserRestoredEventWhenNotDeleted(): void
    {
        $user = new User('user@example.org');
        // User is not deleted (default state)

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(static fn ($event) => $event instanceof UserEvent && $event->getUser() === $user),
                UserEvent::USER_RESET
            );

        $service = new UserResetService(
            $this->manager,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->recoveryTokenHandler,
            $eventDispatcher,
            0
        );

        $service->resetUser($user, 'password123');
    }

    public function testResetUserDoesNotDispatchEventWhenDeleted(): void
    {
        $user = new User('user@example.org');
        $user->setDeleted(true);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $service = new UserResetService(
            $this->manager,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->recoveryTokenHandler,
            $eventDispatcher,
            0
        );

        $service->resetUser($user, 'password123');
    }
}
