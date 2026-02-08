<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use App\Service\UserNotificationRateLimiter;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class UserNotificationRateLimiterTest extends TestCase
{
    private UserNotificationRepository&Stub $repository;
    private CacheItemPoolInterface&Stub $cache;
    private LoggerInterface&Stub $logger;
    private UserNotificationRateLimiter $rateLimiter;
    private User $user;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(UserNotificationRepository::class);
        $this->cache = $this->createStub(CacheItemPoolInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->rateLimiter = new UserNotificationRateLimiter(
            $this->repository,
            $this->cache,
            $this->logger
        );

        $this->user = new User('test@example.org');
        // Set an ID through reflection since it's normally set by Doctrine
        $reflection = new ReflectionClass($this->user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->user, 123);
    }

    public function testIsAllowedWhenCacheHit(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(true);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once())
            ->method('getItem')
            ->with('user_notification_123_password_compromised')
            ->willReturn($cacheItem);

        // Repository should not be called when cache hits
        $repository = $this->createMock(UserNotificationRepository::class);
        $repository->expects($this->never())->method('hasRecentNotification');

        $rateLimiter = new UserNotificationRateLimiter($repository, $cache, $this->logger);
        $result = $rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);

        self::assertFalse($result);
    }

    public function testIsAllowedWhenCacheMissAndNoRecentNotification(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once())
            ->method('getItem')
            ->with('user_notification_123_password_compromised')
            ->willReturn($cacheItem);

        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('hasRecentNotification')
            ->with($this->user, UserNotificationType::PASSWORD_COMPROMISED, 24)
            ->willReturn(false);

        // Cache save should not be called when no recent notification
        $cache->expects($this->never())->method('save');

        $rateLimiter = new UserNotificationRateLimiter($repository, $cache, $this->logger);
        $result = $rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);

        self::assertTrue($result);
    }

    public function testIsAllowedWhenCacheMissAndHasRecentNotification(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with(true);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(24 * 3600);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->exactly(2))
            ->method('getItem')
            ->with('user_notification_123_password_compromised')
            ->willReturn($cacheItem);

        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('hasRecentNotification')
            ->with($this->user, UserNotificationType::PASSWORD_COMPROMISED, 24)
            ->willReturn(true);

        // Cache should be updated when recent notification is found
        $cache
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $rateLimiter = new UserNotificationRateLimiter($repository, $cache, $this->logger);
        $result = $rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);

        self::assertFalse($result);
    }

    public function testIsAllowedWithCustomRateLimitHours(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('hasRecentNotification')
            ->with($this->user, UserNotificationType::PASSWORD_COMPROMISED, 48)
            ->willReturn(false);

        $rateLimiter = new UserNotificationRateLimiter($repository, $cache, $this->logger);
        $result = $rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED, 48);

        self::assertTrue($result);
    }

    public function testIsAllowedWithException(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once())
            ->method('getItem')
            ->willThrowException(new Exception('Cache error'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Error checking notification rate limit',
                $this->callback(static function ($context) {
                    return $context['email'] === 'test@example.org'
                        && $context['type'] === UserNotificationType::PASSWORD_COMPROMISED
                        && $context['error'] === 'Cache error';
                })
            );

        // Should return true (allow) in case of error to be safe
        $rateLimiter = new UserNotificationRateLimiter($this->repository, $cache, $logger);
        $result = $rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);

        self::assertTrue($result);
    }

    public function testSaveSuccess(): void
    {
        $metadata = ['source' => 'login'];

        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($notification) use ($metadata) {
                return $notification instanceof UserNotification
                    && $notification->getUser() === $this->user
                    && $notification->getMetadata() === $metadata;
            }));

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('set')->with(true);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(24 * 3600);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once())
            ->method('getItem')
            ->with('user_notification_123_password_compromised')
            ->willReturn($cacheItem);

        $cache
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $rateLimiter = new UserNotificationRateLimiter($repository, $cache, $this->logger);
        $rateLimiter->save(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            24,
            $metadata
        );
    }

    public function testSaveWithoutMetadata(): void
    {
        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($notification) {
                return $notification instanceof UserNotification
                    && $notification->getUser() === $this->user
                    && $notification->getMetadata() === null;
            }));

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('set')->with(true);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(48 * 3600);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())->method('getItem')->willReturn($cacheItem);
        $cache->expects($this->once())->method('save')->with($cacheItem);

        $rateLimiter = new UserNotificationRateLimiter($repository, $cache, $this->logger);
        $rateLimiter->save(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            48
        );
    }

    public function testSaveWithRepositoryException(): void
    {
        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new Exception('Database error'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Error recording notification rate limit',
                $this->callback(static function ($context) {
                    return $context['email'] === 'test@example.org'
                        && $context['type'] === UserNotificationType::PASSWORD_COMPROMISED
                        && $context['error'] === 'Database error';
                })
            );

        // Cache should not be called if repository fails
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->never())->method('getItem');

        $rateLimiter = new UserNotificationRateLimiter($repository, $cache, $logger);
        $rateLimiter->save(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
        );
    }

    public function testSaveWithCacheException(): void
    {
        $repository = $this->createMock(UserNotificationRepository::class);
        $repository
            ->expects($this->once())
            ->method('save');

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once())
            ->method('getItem')
            ->willThrowException(new Exception('Cache error'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Error caching rate limit',
                $this->callback(static function ($context) {
                    return $context['email'] === 'test@example.org'
                        && $context['type'] === UserNotificationType::PASSWORD_COMPROMISED
                        && $context['error'] === 'Cache error';
                })
            );

        $rateLimiter = new UserNotificationRateLimiter($repository, $cache, $logger);
        $rateLimiter->save(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
        );
    }

    public function testCacheKeyGeneration(): void
    {
        // Test that different users generate different cache keys
        $user2 = new User('user2@example.org');
        $reflection = new ReflectionClass($user2);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($user2, 456);

        $cacheItem1 = $this->createStub(CacheItemInterface::class);
        $cacheItem1->method('isHit')->willReturn(false);

        $cacheItem2 = $this->createStub(CacheItemInterface::class);
        $cacheItem2->method('isHit')->willReturn(false);

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->exactly(2))
            ->method('getItem')
            ->willReturnCallback(static function ($key) use ($cacheItem1, $cacheItem2) {
                return match ($key) {
                    'user_notification_123_password_compromised' => $cacheItem1,
                    'user_notification_456_password_compromised' => $cacheItem2,
                    default => throw new InvalidArgumentException("Unexpected cache key: $key"),
                };
            });

        $this->repository
            ->method('hasRecentNotification')
            ->willReturn(false);

        $rateLimiter = new UserNotificationRateLimiter($this->repository, $cache, $this->logger);
        $rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);
        $rateLimiter->isAllowed($user2, UserNotificationType::PASSWORD_COMPROMISED);
    }
}
