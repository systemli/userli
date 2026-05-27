<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Enum\AliasCacheKey;
use App\Enum\UserCacheKey;
use App\Repository\AliasRepository;
use Override;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class AliasCacheInvalidationStrategy implements InvalidationStrategy
{
    public function __construct(
        private CacheInterface $cache,
        private AliasRepository $aliasRepository,
    ) {
    }

    #[Override]
    public function type(): EntityCacheType
    {
        return EntityCacheType::ALIAS;
    }

    #[Override]
    public function invalidate(string $identifier): void
    {
        foreach (AliasCacheKey::allKeysForSource($identifier) as $key) {
            $this->cache->delete($key);
        }

        $this->cache->delete(UserCacheKey::POSTFIX_QUOTA->key($identifier));

        foreach ($this->aliasRepository->findDestinationsBySource($identifier) as $email) {
            $this->cache->delete(UserCacheKey::POSTFIX_SENDERS->key($email));
        }
    }
}
