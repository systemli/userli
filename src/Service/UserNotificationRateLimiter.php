<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

final readonly class UserNotificationRateLimiter
{
    private const string CACHE_KEY_PREFIX = 'user_notification_';

    public function __construct(
        private UserNotificationRepository $repository,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Determines if the user is allowed to perform a certain action
     * based on the notification type and rate limit period.
     *
     * This method checks the cache for the given user and notification type
     * to ensure no recent actions have been performed within the rate limit period.
     * If no recent actions are found in the database or cache, the user is
     * allowed to proceed. Logs any errors encountered during the check.
     *
     * @param User                 $user           the user being checked
     * @param UserNotificationType $type           the type of notification being considered
     * @param int                  $rateLimitHours the rate limit period in hours (default is 24)
     *
     * @return bool true if the user is allowed, otherwise false
     */
    public function isAllowed(User $user, UserNotificationType $type, int $rateLimitHours = 24): bool
    {
        try {
            $cacheKey = $this->getCacheKey($user, $type);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return false;
            }

            $hasRecentNotification = $this->repository->hasRecentNotification($user, $type, $rateLimitHours);
            if ($hasRecentNotification) {
                $this->setCacheItem($user, $type, $rateLimitHours);

                return false;
            }
        } catch (Exception $exception) {
            $this->logger->error('Error checking notification rate limit', [
                'email' => $user->getEmail(),
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Save a user notification and set a cache item for rate limiting.
     *
     * @param User                 $user           the user for whom the notification is saved
     * @param UserNotificationType $type           the type of notification
     * @param int                  $rateLimitHours The number of hours for the rate limit. Defaults to 24 hours.
     * @param array|null           $metadata       optional metadata associated with the notification
     */
    public function save(User $user, UserNotificationType $type, int $rateLimitHours = 24, ?array $metadata = null): void
    {
        try {
            $notification = new UserNotification($user, $type, $metadata);
            $this->repository->save($notification);

            $this->setCacheItem($user, $type, $rateLimitHours);
        } catch (Exception $exception) {
            $this->logger->error('Error recording notification rate limit', [
                'email' => $user->getEmail(),
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function setCacheItem(User $user, UserNotificationType $type, int $rateLimitHours): void
    {
        try {
            $cacheKey = $this->getCacheKey($user, $type);
            $cacheItem = $this->cache->getItem($cacheKey);

            // Set cache value (the actual value doesn't matter, just the existence)
            $cacheItem->set(true);

            // Set expiration to rate limit hours
            $cacheItem->expiresAfter($rateLimitHours * 3600);

            $this->cache->save($cacheItem);
        } catch (Exception $exception) {
            $this->logger->error('Error caching rate limit', [
                'email' => $user->getEmail(),
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function getCacheKey(User $user, UserNotificationType $type): string
    {
        return self::CACHE_KEY_PREFIX.$user->getId().'_'.$type->value;
    }
}
