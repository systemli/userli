<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Event\UserEvent;
use App\Service\UserResetService;
use App\Service\UserRestoreService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserRestoreServiceTest extends TestCase
{
    private EntityManagerInterface&Stub $manager;
    private EventDispatcherInterface&Stub $eventDispatcher;
    private UserResetService&Stub $userResetService;
    private UserRestoreService $service;

    protected function setUp(): void
    {
        $this->manager = $this->createStub(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $this->userResetService = $this->createStub(UserResetService::class);

        $this->service = new UserRestoreService(
            $this->manager,
            $this->eventDispatcher,
            $this->userResetService
        );
    }

    public function testRestoreUserCallsResetService(): void
    {
        $user = new User('deleted@example.org');
        $user->setDeleted(true);
        $password = 'newSecurePassword123';

        $userResetService = $this->createMock(UserResetService::class);
        $userResetService
            ->expects($this->once())
            ->method('resetUser')
            ->with($user, $password)
            ->willReturn(null);

        $service = new UserRestoreService(
            $this->manager,
            $this->eventDispatcher,
            $userResetService
        );

        $service->restoreUser($user, $password);
    }

    public function testRestoreUserSetsDeletedToFalse(): void
    {
        $user = new User('deleted@example.org');
        $user->setDeleted(true);

        $this->service->restoreUser($user, 'newPassword123');

        self::assertFalse($user->isDeleted());
    }

    public function testRestoreUserReturnsRecoveryTokenFromResetService(): void
    {
        $user = new User('deleted@example.org');
        $user->setDeleted(true);

        $userResetService = $this->createMock(UserResetService::class);
        $userResetService
            ->expects($this->once())
            ->method('resetUser')
            ->willReturn('generated-recovery-token');

        $service = new UserRestoreService(
            $this->manager,
            $this->eventDispatcher,
            $userResetService
        );

        $recoveryToken = $service->restoreUser($user, 'password123');

        self::assertEquals('generated-recovery-token', $recoveryToken);
    }

    public function testRestoreUserFlushesEntityManager(): void
    {
        $user = new User('deleted@example.org');
        $user->setDeleted(true);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('flush');

        $service = new UserRestoreService(
            $manager,
            $this->eventDispatcher,
            $this->userResetService
        );

        $service->restoreUser($user, 'password123');
    }

    public function testRestoreUserDispatchesUserCreatedEvent(): void
    {
        $user = new User('deleted@example.org');
        $user->setDeleted(true);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(static fn ($event) => $event instanceof UserEvent && $event->getUser() === $user),
                UserEvent::USER_CREATED
            );

        $service = new UserRestoreService(
            $this->manager,
            $eventDispatcher,
            $this->userResetService
        );

        $service->restoreUser($user, 'password123');
    }
}
