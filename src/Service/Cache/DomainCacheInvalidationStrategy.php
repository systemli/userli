<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Enum\DomainCacheKey;
use Override;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class DomainCacheInvalidationStrategy implements InvalidationStrategy
{
    public function __construct(private CacheInterface $cache)
    {
    }

    #[Override]
    public function type(): EntityCacheType
    {
        return EntityCacheType::DOMAIN;
    }

    #[Override]
    public function invalidate(string $identifier): void
    {
        foreach (DomainCacheKey::allKeysForName($identifier) as $key) {
            $this->cache->delete($key);
        }
    }
}
