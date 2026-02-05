<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Event\UserEvent;
use App\Service\UserResetService;
use App\Service\UserRestoreService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserRestoreServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $manager;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private UserResetService&MockObject $userResetService;
    private UserRestoreService $service;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->userResetService = $this->createMock(UserResetService::class);

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

        $this->userResetService
            ->expects($this->once())
            ->method('resetUser')
            ->with($user, $password)
            ->willReturn(null);

        $this->service->restoreUser($user, $password);
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

        $this->userResetService
            ->expects($this->once())
            ->method('resetUser')
            ->willReturn('generated-recovery-token');

        $recoveryToken = $this->service->restoreUser($user, 'password123');

        self::assertEquals('generated-recovery-token', $recoveryToken);
    }

    public function testRestoreUserFlushesEntityManager(): void
    {
        $user = new User('deleted@example.org');
        $user->setDeleted(true);

        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->service->restoreUser($user, 'password123');
    }

    public function testRestoreUserDispatchesUserCreatedEvent(): void
    {
        $user = new User('deleted@example.org');
        $user->setDeleted(true);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(static fn ($event) => $event instanceof UserEvent && $event->getUser() === $user),
                UserEvent::USER_CREATED
            );

        $this->service->restoreUser($user, 'password123');
    }
}
