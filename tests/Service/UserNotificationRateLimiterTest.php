<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use App\Service\UserNotificationRateLimiter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class UserNotificationRateLimiterTest extends TestCase
{
    private UserNotificationRepository|MockObject $repository;
    private CacheItemPoolInterface|MockObject $cache;
    private LoggerInterface|MockObject $logger;
    private UserNotificationRateLimiter $rateLimiter;
    private User $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserNotificationRepository::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->rateLimiter = new UserNotificationRateLimiter(
            $this->repository,
            $this->cache,
            $this->logger
        );

        $this->user = new User();
        $this->user->setEmail('test@example.org');
        // Set an ID through reflection since it's normally set by Doctrine
        $reflection = new \ReflectionClass($this->user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->user, 123);
    }

    public function testIsAllowedWhenCacheHit(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(true);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('user_notification_123_password_compromised')
            ->willReturn($cacheItem);

        // Repository should not be called when cache hits
        $this->repository->expects($this->never())->method('hasRecentNotification');

        $result = $this->rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);

        $this->assertFalse($result);
    }

    public function testIsAllowedWhenCacheMissAndNoRecentNotification(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('user_notification_123_password_compromised')
            ->willReturn($cacheItem);

        $this->repository
            ->expects($this->once())
            ->method('hasRecentNotification')
            ->with($this->user, UserNotificationType::PASSWORD_COMPROMISED, 24)
            ->willReturn(false);

        // Cache save should not be called when no recent notification
        $this->cache->expects($this->never())->method('save');

        $result = $this->rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);

        $this->assertTrue($result);
    }

    public function testIsAllowedWhenCacheMissAndHasRecentNotification(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with(true);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(24 * 3600);

        $this->cache
            ->expects($this->exactly(2))
            ->method('getItem')
            ->with('user_notification_123_password_compromised')
            ->willReturn($cacheItem);

        $this->repository
            ->expects($this->once())
            ->method('hasRecentNotification')
            ->with($this->user, UserNotificationType::PASSWORD_COMPROMISED, 24)
            ->willReturn(true);

        // Cache should be updated when recent notification is found
        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $result = $this->rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);

        $this->assertFalse($result);
    }

    public function testIsAllowedWithCustomRateLimitHours(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->repository
            ->expects($this->once())
            ->method('hasRecentNotification')
            ->with($this->user, UserNotificationType::PASSWORD_COMPROMISED, 48)
            ->willReturn(false);

        $result = $this->rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED, 48);

        $this->assertTrue($result);
    }

    public function testIsAllowedWithException(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->willThrowException(new \Exception('Cache error'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Error checking notification rate limit',
                $this->callback(function ($context) {
                    return $context['email'] === 'test@example.org' &&
                           $context['type'] === UserNotificationType::PASSWORD_COMPROMISED &&
                           $context['error'] === 'Cache error';
                })
            );

        // Should return true (allow) in case of error to be safe
        $result = $this->rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);

        $this->assertTrue($result);
    }

    public function testSaveSuccess(): void
    {
        $locale = 'de';
        $metadata = ['source' => 'login'];

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($notification) use ($locale, $metadata) {
                return $notification instanceof UserNotification &&
                       $notification->getUser() === $this->user &&
                       $notification->getLocale() === $locale &&
                       $notification->getMetadata() === $metadata;
            }));

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('set')->with(true);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(24 * 3600);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('user_notification_123_password_compromised')
            ->willReturn($cacheItem);

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $this->rateLimiter->save(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            $locale,
            24,
            $metadata
        );
    }

    public function testSaveWithoutMetadata(): void
    {
        $locale = 'en';

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($notification) use ($locale) {
                return $notification instanceof UserNotification &&
                       $notification->getUser() === $this->user &&
                       $notification->getLocale() === $locale &&
                       $notification->getMetadata() === null;
            }));

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('set')->with(true);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(48 * 3600);

        $this->cache->expects($this->once())->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save')->with($cacheItem);

        $this->rateLimiter->save(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            $locale,
            48
        );
    }

    public function testSaveWithRepositoryException(): void
    {
        $locale = 'fr';

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Database error'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Error recording notification rate limit',
                $this->callback(function ($context) {
                    return $context['email'] === 'test@example.org' &&
                           $context['type'] === UserNotificationType::PASSWORD_COMPROMISED &&
                           $context['error'] === 'Database error';
                })
            );

        // Cache should not be called if repository fails
        $this->cache->expects($this->never())->method('getItem');

        $this->rateLimiter->save(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            $locale
        );
    }

    public function testSaveWithCacheException(): void
    {
        $locale = 'es';

        $this->repository
            ->expects($this->once())
            ->method('save');

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->willThrowException(new \Exception('Cache error'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Error caching rate limit',
                $this->callback(function ($context) {
                    return $context['email'] === 'test@example.org' &&
                           $context['type'] === UserNotificationType::PASSWORD_COMPROMISED &&
                           $context['error'] === 'Cache error';
                })
            );

        $this->rateLimiter->save(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            $locale
        );
    }

    public function testCacheKeyGeneration(): void
    {
        // Test that different users generate different cache keys
        $user2 = new User();
        $user2->setEmail('user2@example.org');
        $reflection = new \ReflectionClass($user2);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user2, 456);

        $cacheItem1 = $this->createMock(CacheItemInterface::class);
        $cacheItem1->method('isHit')->willReturn(false);

        $cacheItem2 = $this->createMock(CacheItemInterface::class);
        $cacheItem2->method('isHit')->willReturn(false);

        $this->cache
            ->expects($this->exactly(2))
            ->method('getItem')
            ->withConsecutive(
                ['user_notification_123_password_compromised'],
                ['user_notification_456_password_compromised']
            )
            ->willReturnOnConsecutiveCalls($cacheItem1, $cacheItem2);

        $this->repository
            ->method('hasRecentNotification')
            ->willReturn(false);

        $this->rateLimiter->isAllowed($this->user, UserNotificationType::PASSWORD_COMPROMISED);
        $this->rateLimiter->isAllowed($user2, UserNotificationType::PASSWORD_COMPROMISED);
    }
}
