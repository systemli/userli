<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserLastLoginUpdateService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Random\RandomException;

// Load the mock function at the class level to ensure it's available before service instantiation
require_once __DIR__ . '/RandomIntMock.php';

class UserLastLoginUpdateServiceTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private LoggerInterface|MockObject $logger;
    private UserLastLoginUpdateService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new UserLastLoginUpdateService($this->entityManager, $this->logger);

        // Ensure the mock flag is reset for each test
        $GLOBALS['test_random_int_should_throw'] = false;
    }

    protected function tearDown(): void
    {
        // Clean up the mock flag after each test
        $GLOBALS['test_random_int_should_throw'] = false;
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

        // Capture time range before calling the service
        $beforeCall = new DateTime();
        $maxObfuscation = (new DateTime())->modify('-12 hours');

        // Act
        $this->service->updateLastLogin($user);

        // Assert
        $lastLoginTime = $user->getLastLoginTime();
        $this->assertInstanceOf(DateTime::class, $lastLoginTime);

        // The obfuscated time should be between 0-12 hours ago from the time of the call
        // Add a small buffer (1 second) to account for execution time
        $afterCall = (new DateTime())->modify('+1 second');
        $this->assertLessThanOrEqual($afterCall->getTimestamp(), $lastLoginTime->getTimestamp());
        $this->assertGreaterThanOrEqual($maxObfuscation->getTimestamp(), $lastLoginTime->getTimestamp());
    }

    public function testUpdateLastLoginWithOldLastLogin(): void
    {
        // Arrange
        $user = new User();
        $oldLastLogin = (new DateTime())->modify('-72 hours'); // 3 days ago
        $user->setLastLoginTime($oldLastLogin);

        // Assert that persist is called exactly once
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        // Capture time range before calling the service
        $beforeCall = new DateTime();
        $maxObfuscation = (new DateTime())->modify('-12 hours');

        // Act
        $this->service->updateLastLogin($user);

        // Assert
        $lastLoginTime = $user->getLastLoginTime();
        $this->assertInstanceOf(DateTime::class, $lastLoginTime);
        $this->assertNotEquals($oldLastLogin, $lastLoginTime);

        // The obfuscated time should be between 0-12 hours ago from the time of the call
        // Add a small buffer (1 second) to account for execution time
        $afterCall = (new DateTime())->modify('+1 second');
        $this->assertLessThanOrEqual($afterCall->getTimestamp(), $lastLoginTime->getTimestamp());
        $this->assertGreaterThanOrEqual($maxObfuscation->getTimestamp(), $lastLoginTime->getTimestamp());
    }

    public function testUpdateLastLoginSkipsWhenRecentLogin(): void
    {
        // Arrange
        $user = new User();
        $recentLastLogin = (new DateTime())->modify('-24 hours'); // 1 day ago (within 48 hours)
        $user->setLastLoginTime($recentLastLogin);

        // Assert that persist is never called
        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        // Act
        $this->service->updateLastLogin($user);

        // Assert
        $this->assertEquals($recentLastLogin, $user->getLastLoginTime());
    }

    public function testUpdateLastLoginSkipsWhenVeryRecentLogin(): void
    {
        // Arrange
        $user = new User();
        $veryRecentLastLogin = (new DateTime())->modify('-1 hour'); // 1 hour ago
        $user->setLastLoginTime($veryRecentLastLogin);

        // Assert that persist is never called
        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        // Act
        $this->service->updateLastLogin($user);

        // Assert
        $this->assertEquals($veryRecentLastLogin, $user->getLastLoginTime());
    }

    public function testUpdateLastLoginWithExactly48HoursAgo(): void
    {
        // Arrange
        $user = new User();
        $exactly48HoursAgo = (new DateTime())->modify('-48 hours');
        $user->setLastLoginTime($exactly48HoursAgo);

        // Assert that persist is called exactly once (exactly 48 hours should trigger update)
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        // Capture time range before calling the service
        $beforeCall = new DateTime();
        $maxObfuscation = (new DateTime())->modify('-12 hours');

        // Act
        $this->service->updateLastLogin($user);

        // Assert
        $lastLoginTime = $user->getLastLoginTime();
        $this->assertInstanceOf(DateTime::class, $lastLoginTime);
        $this->assertNotEquals($exactly48HoursAgo, $lastLoginTime);

        // The obfuscated time should be between 0-12 hours ago from the time of the call
        // Add a small buffer (1 second) to account for execution time
        $afterCall = (new DateTime())->modify('+1 second');
        $this->assertLessThanOrEqual($afterCall->getTimestamp(), $lastLoginTime->getTimestamp());
        $this->assertGreaterThanOrEqual($maxObfuscation->getTimestamp(), $lastLoginTime->getTimestamp());
    }

    public function testUpdateLastLoginWithJustOver48HoursAgo(): void
    {
        // Arrange
        $user = new User();
        $justOver48HoursAgo = (new DateTime())->modify('-50 hours'); // Use 50 hours to be clearly outside the range
        $user->setLastLoginTime($justOver48HoursAgo);

        // Assert that persist is called exactly once
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        // Act
        $this->service->updateLastLogin($user);

        // Assert
        $lastLoginTime = $user->getLastLoginTime();
        $this->assertInstanceOf(DateTime::class, $lastLoginTime);
        // Since the obfuscation can be 0-12 hours, the new time should be much more recent
        // than 50 hours ago, regardless of the random obfuscation
        $this->assertGreaterThan($justOver48HoursAgo->getTimestamp(), $lastLoginTime->getTimestamp());

        // Verify the time is within the expected range (0-12 hours ago from now)
        $now = new DateTime();
        $twelveHoursAgo = (new DateTime())->modify('-12 hours');

        $this->assertLessThanOrEqual($now->getTimestamp(), $lastLoginTime->getTimestamp());
        $this->assertGreaterThanOrEqual($twelveHoursAgo->getTimestamp(), $lastLoginTime->getTimestamp());
    }

    public function testObfuscationVariability(): void
    {
        // This test verifies that the obfuscation creates different times
        // by running the method multiple times and checking for variance

        $timestamps = [];

        for ($i = 0; $i < 10; $i++) {
            // Create a fresh service instance for each iteration to reset mock expectations
            $entityManager = $this->createMock(EntityManagerInterface::class);
            $entityManager->expects($this->once())->method('persist');
            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects($this->never())->method('error');
            $service = new UserLastLoginUpdateService($entityManager, $logger);

            $user = new User();
            $user->setLastLoginTime(null);

            $service->updateLastLogin($user);
            $timestamps[] = $user->getLastLoginTime()->getTimestamp();
        }

        // Check that we have some variance in the timestamps
        // (not all timestamps should be identical due to random obfuscation)
        $uniqueTimestamps = array_unique($timestamps);
        $this->assertGreaterThan(1, count($uniqueTimestamps),
            'Obfuscation should create some variance in timestamps');
    }

    public function testUpdateLastLoginHandlesRandomException(): void
    {
        // Arrange
        $user = new User();
        $user->setLastLoginTime(null);

        // Set up expectations - when exception occurs, persist should not be called
        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        // Logger should be called with error details
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to update last login time',
                $this->callback(function ($context) use ($user) {
                    return isset($context['email'])
                        && $context['email'] === $user->getEmail()
                        && isset($context['error'])
                        && str_contains($context['error'], 'RandomException');
                })
            );

        // Enable the mock to throw exception
        $GLOBALS['test_random_int_should_throw'] = true;

        try {
            // Act
            $this->service->updateLastLogin($user);

            // Assert - the lastLoginTime should remain null since the exception prevented the update
            $this->assertNull($user->getLastLoginTime(), 'LastLoginTime should remain null when exception occurs');
        } finally {
            // Clean up: disable the mock
            $GLOBALS['test_random_int_should_throw'] = false;
        }
    }
}
