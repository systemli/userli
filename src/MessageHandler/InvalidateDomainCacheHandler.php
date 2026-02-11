<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\DomainCacheKey;
use App\Message\InvalidateDomainCache;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
final readonly class InvalidateDomainCacheHandler
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function __invoke(InvalidateDomainCache $message): void
    {
        foreach (DomainCacheKey::allKeysForName($message->name) as $key) {
            $this->cache->delete($key);
        }
    }
}
