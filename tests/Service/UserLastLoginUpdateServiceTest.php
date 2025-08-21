<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserLastLoginUpdateService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserLastLoginUpdateServiceTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private UserLastLoginUpdateService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new UserLastLoginUpdateService($this->entityManager);
    }

    public function testUpdateLastLoginWithNoExistingLastLogin(): void
    {
        // Arrange
        $user = new User();
        $user->setLastLoginTime(null);

        // Assert that persist is called exactly once
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->service->updateLastLogin($user);

        // Assert
        $lastLoginTime = $user->getLastLoginTime();
        $this->assertInstanceOf(DateTime::class, $lastLoginTime);

        // Verify it's set to the start of the current week (Monday at 00:00:00)
        $expectedWeekStart = new DateTime();
        $expectedWeekStart->modify('monday this week');
        $expectedWeekStart->setTime(0, 0, 0);

        $this->assertEquals($expectedWeekStart->getTimestamp(), $lastLoginTime->getTimestamp());
    }

    public function testUpdateLastLoginWithOldLastLogin(): void
    {
        // Arrange
        $user = new User();
        $oldLastLogin = (new DateTime())->modify('-1 week'); // Last week
        $user->setLastLoginTime($oldLastLogin);

        // Assert that persist is called exactly once
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->service->updateLastLogin($user);

        // Assert
        $lastLoginTime = $user->getLastLoginTime();
        $this->assertInstanceOf(DateTime::class, $lastLoginTime);
        $this->assertNotEquals($oldLastLogin->getTimestamp(), $lastLoginTime->getTimestamp());

        // Verify it's set to the start of the current week
        $expectedWeekStart = new DateTime();
        $expectedWeekStart->modify('monday this week');
        $expectedWeekStart->setTime(0, 0, 0);

        $this->assertEquals($expectedWeekStart->getTimestamp(), $lastLoginTime->getTimestamp());
    }

    public function testUpdateLastLoginDoesNotUpdateWhenAlreadyCurrentWeek(): void
    {
        // Arrange
        $user = new User();

        // Set to current week start (same timestamp)
        $currentWeekStart = new DateTime();
        $currentWeekStart->modify('monday this week');
        $currentWeekStart->setTime(0, 0, 0);
        $user->setLastLoginTime($currentWeekStart);

        // Assert that persist is NOT called since the value is already current
        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        // Act
        $this->service->updateLastLogin($user);

        // Assert - the time should remain unchanged
        $lastLoginTime = $user->getLastLoginTime();
        $this->assertEquals($currentWeekStart->getTimestamp(), $lastLoginTime->getTimestamp());
    }
}
