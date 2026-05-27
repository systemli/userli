<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Enum\OpenPgpKeyCacheKey;
use Override;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class OpenPgpKeyCacheInvalidationStrategy implements InvalidationStrategy
{
    public function __construct(private CacheInterface $cache)
    {
    }

    #[Override]
    public function type(): EntityCacheType
    {
        return EntityCacheType::OPENPGP_KEY;
    }

    #[Override]
    public function invalidate(string $identifier): void
    {
        foreach (OpenPgpKeyCacheKey::allKeysForEmail($identifier) as $key) {
            $this->cache->delete($key);
        }
    }
}
