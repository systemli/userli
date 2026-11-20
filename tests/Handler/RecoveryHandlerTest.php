<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\AppPassword;
use App\Entity\User;
use App\Enum\RecoveryStatus;
use App\Exception\RecoveryException;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use App\Repository\AppPasswordRepository;
use App\Repository\UserRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RecoveryHandlerTest extends TestCase
{
    private EntityManagerInterface $manager;
    private UserRepository $userRepository;
    private RecoveryTokenHandler $recoveryTokenHandler;
    private MailCryptKeyHandler $mailCryptKeyHandler;
    private PasswordUpdater $passwordUpdater;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->manager = $this->createStub(EntityManagerInterface::class);
        $this->manager->method('getRepository')->willReturn($this->userRepository);
        $this->recoveryTokenHandler = $this->createStub(RecoveryTokenHandler::class);
        $this->mailCryptKeyHandler = $this->createStub(MailCryptKeyHandler::class);
        $this->passwordUpdater = $this->createStub(PasswordUpdater::class);
        $this->eventDispatcher = $this->createStub(EventDispatcherInterface::class);
    }

    private function createHandler(
        ?EntityManagerInterface $manager = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?RecoveryTokenHandler $recoveryTokenHandler = null,
    ): RecoveryHandler {
        return new RecoveryHandler(
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $recoveryTokenHandler ?? $this->recoveryTokenHandler,
            $eventDispatcher ?? $this->eventDispatcher,
            $manager ?? $this->manager,
        );
    }

    public function testStartRecoveryWithInvalidEmail(): void
    {
        $this->userRepository->method('findByEmail')->willReturn(null);

        $handler = $this->createHandler();
        $result = $handler->startRecovery('unknown@example.org', 'some-token');

        self::assertSame(RecoveryStatus::Invalid, $result->status);
        self::assertNull($result->activeTime);
        self::assertNull($result->recoveryToken);
    }

    public function testStartRecoveryWithInvalidToken(): void
    {
        $user = new User('test@example.org');
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(false);

        $handler = $this->createHandler();
        $result = $handler->startRecovery('test@example.org', 'wrong-token');

        self::assertSame(RecoveryStatus::Invalid, $result->status);
    }

    public function testStartRecoveryStartsNewProcess(): void
    {
        $user = new User('test@example.org');
        // No recoveryStartTime set => null => process gets started
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch');

        $handler = $this->createHandler(eventDispatcher: $eventDispatcher);
        $result = $handler->startRecovery('test@example.org', 'valid-token');

        self::assertSame(RecoveryStatus::Started, $result->status);
        self::assertNull($result->recoveryToken);
        self::assertNotNull($user->getRecoveryStartTime());
        self::assertEquals($user->getRecoveryStartTime()->add(new DateInterval('P2D')), $result->activeTime);
    }

    public function testStartRecoveryWithExpiredProcess(): void
    {
        $user = new User('test@example.org');
        // Set recoveryStartTime to 31 days ago => expired => restarts
        $user->setRecoveryStartTime(new DateTimeImmutable('-31 days'));
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch');

        $handler = $this->createHandler(eventDispatcher: $eventDispatcher);
        $result = $handler->startRecovery('test@example.org', 'valid-token');

        self::assertSame(RecoveryStatus::Started, $result->status);
        self::assertGreaterThan(new DateTimeImmutable('-1 minute'), $user->getRecoveryStartTime());
        self::assertEquals($user->getRecoveryStartTime()->add(new DateInterval('P2D')), $result->activeTime);
    }

    public function testStartRecoveryPending(): void
    {
        $user = new User('test@example.org');
        // Set recoveryStartTime to 1 day ago => pending (within 2-day delay)
        $recoveryStartTime = new DateTimeImmutable('-1 day');
        $user->setRecoveryStartTime($recoveryStartTime);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $handler = $this->createHandler();
        $result = $handler->startRecovery('test@example.org', 'valid-token');

        self::assertSame(RecoveryStatus::Pending, $result->status);
        self::assertEquals($recoveryStartTime->add(new DateInterval('P2D')), $result->activeTime);
        self::assertNull($result->recoveryToken);
    }

    public function testStartRecoveryReady(): void
    {
        $user = new User('test@example.org');
        // Set recoveryStartTime to 3 days ago => ready (delay elapsed)
        $user->setRecoveryStartTime(new DateTimeImmutable('-3 days'));
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $handler = $this->createHandler();
        $result = $handler->startRecovery('test@example.org', 'valid-token');

        self::assertSame(RecoveryStatus::Ready, $result->status);
        self::assertNotNull($result->recoveryToken);
        self::assertEquals('valid-token', $result->recoveryToken);
    }

    public function testResetPasswordWithInvalidEmail(): void
    {
        $this->userRepository->method('findByEmail')->willReturn(null);

        $handler = $this->createHandler();

        $this->expectException(RecoveryException::class);
        $this->expectExceptionMessage('Invalid email or recovery token');
        $handler->resetPassword('unknown@example.org', 'token', 'newpassword');
    }

    public function testResetPasswordWithInvalidToken(): void
    {
        $user = new User('test@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('-3 days'));
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(false);

        $handler = $this->createHandler();

        $this->expectException(RecoveryException::class);
        $this->expectExceptionMessage('Invalid email or recovery token');
        $handler->resetPassword('test@example.org', 'wrong-token', 'newpassword');
    }

    public function testResetPasswordSuccess(): void
    {
        $user = new User('test@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('-3 days'));
        $user->setPlainMailCryptPrivateKey('privateKey');
        $user->setTotpSecret('old-secret');
        $user->setTotpConfirmed(true);
        $user->setTotpBackupCodes(['code1', 'code2']);

        $this->userRepository->method('findByEmail')->willReturn($user);

        $recoveryTokenHandler = $this->createMock(RecoveryTokenHandler::class);
        $recoveryTokenHandler->method('verify')->willReturn(true);
        $recoveryTokenHandler->method('decrypt')->willReturn('decryptedPrivateKey');
        $recoveryTokenHandler->expects($this->once())->method('create')
            ->willReturnCallback(static function (User $user): void {
                $user->setPlainRecoveryToken('new-recovery-token');
            });

        $handler = $this->createHandler(recoveryTokenHandler: $recoveryTokenHandler);
        $newToken = $handler->resetPassword('test@example.org', 'valid-token', 'newpassword');

        self::assertEquals('new-recovery-token', $newToken);
        self::assertNull($user->getRecoveryStartTime());
        self::assertFalse($user->isTotpAuthenticationEnabled());
        self::assertNull($user->getTotpSecret());
        self::assertEmpty($user->getTotpBackupCodes());
    }

    public function testResetPasswordDeletesAppPasswords(): void
    {
        $user = new User('test@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('-3 days'));
        $this->userRepository->method('findByEmail')->willReturn($user);

        $appPasswordRepository = $this->createMock(AppPasswordRepository::class);
        $appPasswordRepository->expects($this->once())->method('removeByUser')->with($user);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [User::class, $this->userRepository],
            [AppPassword::class, $appPasswordRepository],
        ]);

        $recoveryTokenHandler = $this->createStub(RecoveryTokenHandler::class);
        $recoveryTokenHandler->method('verify')->willReturn(true);
        $recoveryTokenHandler->method('decrypt')->willReturn('decryptedPrivateKey');
        $recoveryTokenHandler->method('create')
            ->willReturnCallback(static function (User $user): void {
                $user->setPlainRecoveryToken('new-recovery-token');
            });

        $this->createHandler(manager: $manager, recoveryTokenHandler: $recoveryTokenHandler)
            ->resetPassword('test@example.org', 'valid-token', 'newpassword');
    }

    public function testVerifyRecoveryTokenWithEmail(): void
    {
        $user = new User('test@example.org');
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $handler = $this->createHandler();

        self::assertTrue($handler->verifyRecoveryToken('test@example.org', 'valid-token'));
    }

    public function testVerifyRecoveryTokenWithUnknownEmail(): void
    {
        $this->userRepository->method('findByEmail')->willReturn(null);

        $handler = $this->createHandler();

        self::assertFalse($handler->verifyRecoveryToken('unknown@example.org', 'valid-token'));
    }

    public function testVerifyRecoveryTokenWithTimeCheckPending(): void
    {
        $user = new User('test@example.org');
        // Set recoveryStartTime to 1 day ago => still within delay
        $user->setRecoveryStartTime(new DateTimeImmutable('-1 day'));
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $handler = $this->createHandler();

        self::assertFalse($handler->verifyRecoveryToken('test@example.org', 'valid-token', true));
    }

    public function testVerifyRecoveryTokenWithTimeCheckReady(): void
    {
        $user = new User('test@example.org');
        // Set recoveryStartTime to 3 days ago => delay elapsed
        $user->setRecoveryStartTime(new DateTimeImmutable('-3 days'));
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $handler = $this->createHandler();

        self::assertTrue($handler->verifyRecoveryToken('test@example.org', 'valid-token', true));
    }

    public function testVerifyRecoveryTokenWithTimeCheckExpired(): void
    {
        $user = new User('test@example.org');
        // Set recoveryStartTime to 31 days ago => process expired
        $user->setRecoveryStartTime(new DateTimeImmutable('-31 days'));
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $handler = $this->createHandler();

        self::assertFalse($handler->verifyRecoveryToken('test@example.org', 'valid-token', true));
    }

    public function testResetPasswordWithExpiredProcess(): void
    {
        $user = new User('test@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('-31 days'));
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $handler = $this->createHandler();

        $this->expectException(RecoveryException::class);
        $handler->resetPassword('test@example.org', 'valid-token', 'new-password');
    }

    public function testVerifyRecoveryTokenWithTimeCheckNoStartTime(): void
    {
        $user = new User('test@example.org');
        // No recoveryStartTime => verifyTime fails
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $handler = $this->createHandler();

        self::assertFalse($handler->verifyRecoveryToken('test@example.org', 'valid-token', true));
    }

    public function testVerifyRecoveryTokenWithUserObject(): void
    {
        $user = new User('test@example.org');
        $this->recoveryTokenHandler->method('verify')->willReturn(true);

        $handler = $this->createHandler();

        self::assertTrue($handler->verifyRecoveryToken($user, 'valid-token'));
    }

    public function testCancelRecoveryErasesStartTimeAndFlushes(): void
    {
        $user = new User('test@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable());

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('flush');

        $this->createHandler(manager: $manager)->cancelRecovery($user);

        self::assertNull($user->getRecoveryStartTime());
    }

    public function testGetExpireTime(): void
    {
        $start = new DateTimeImmutable('2026-01-15 10:00:00');

        self::assertEquals(new DateTimeImmutable('2026-02-14 10:00:00'), RecoveryHandler::getExpireTime($start));
    }

    public function testIsInProgress(): void
    {
        self::assertFalse(RecoveryHandler::isInProgress(null));
        self::assertTrue(RecoveryHandler::isInProgress(new DateTimeImmutable()));
        self::assertTrue(RecoveryHandler::isInProgress(new DateTimeImmutable('-29 days')));
        self::assertFalse(RecoveryHandler::isInProgress(new DateTimeImmutable('-31 days')));
    }
}
