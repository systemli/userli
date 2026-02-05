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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserResetServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $manager;
    private PasswordUpdater&MockObject $passwordUpdater;
    private MailCryptKeyHandler&MockObject $mailCryptKeyHandler;
    private RecoveryTokenHandler&MockObject $recoveryTokenHandler;
    private EventDispatcherInterface&MockObject $eventDispatcher;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(EntityManagerInterface::class);
        $this->passwordUpdater = $this->createMock(PasswordUpdater::class);
        $this->mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);
        $this->recoveryTokenHandler = $this->createMock(RecoveryTokenHandler::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
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
        $service = $this->createService(0);
        $user = new User('user@example.org');
        $password = 'newSecurePassword123';

        $this->passwordUpdater
            ->expects($this->once())
            ->method('updatePassword')
            ->with($user, $password);

        $this->manager
            ->expects($this->once())
            ->method('flush');

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
        $service = $this->createService(2); // MAIL_CRYPT=2 (enforce for new users)
        $user = new User('user@example.org');
        $password = 'newSecurePassword123';

        $this->mailCryptKeyHandler
            ->expects($this->once())
            ->method('create')
            ->with($user, $password, true);

        $this->recoveryTokenHandler
            ->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturnCallback(static function (User $user): void {
                $user->setPlainRecoveryToken('generated-recovery-token');
            });

        $recoveryToken = $service->resetUser($user, $password);

        self::assertEquals('generated-recovery-token', $recoveryToken);
    }

    public function testResetUserWithoutMailCryptDoesNotRegenerateKeys(): void
    {
        $service = $this->createService(0); // MAIL_CRYPT=0 (disabled)
        $user = new User('user@example.org');
        $password = 'newSecurePassword123';

        $this->mailCryptKeyHandler
            ->expects($this->never())
            ->method('create');

        $this->recoveryTokenHandler
            ->expects($this->never())
            ->method('create');

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
        $service = $this->createService(0);
        $user = new User('user@example.org');

        $this->manager
            ->expects($this->once())
            ->method('flush');

        $service->resetUser($user, 'password123');
    }

    public function testResetUserDispatchesUserRestoredEventWhenNotDeleted(): void
    {
        $service = $this->createService(0);
        $user = new User('user@example.org');
        // User is not deleted (default state)

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(static fn ($event) => $event instanceof UserEvent && $event->getUser() === $user),
                UserEvent::USER_RESTORED
            );

        $service->resetUser($user, 'password123');
    }

    public function testResetUserDoesNotDispatchEventWhenDeleted(): void
    {
        $service = $this->createService(0);
        $user = new User('user@example.org');
        $user->setDeleted(true);

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $service->resetUser($user, 'password123');
    }
}
