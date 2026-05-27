<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Enum\UserCacheKey;
use Override;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class UserCacheInvalidationStrategy implements InvalidationStrategy
{
    public function __construct(private CacheInterface $cache)
    {
    }

    #[Override]
    public function type(): EntityCacheType
    {
        return EntityCacheType::USER;
    }

    #[Override]
    public function invalidate(string $identifier): void
    {
        foreach (UserCacheKey::allKeysForEmail($identifier) as $key) {
            $this->cache->delete($key);
        }
    }
}
