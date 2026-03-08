<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ClearCache;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class ClearCacheHandler
{
    public function __construct(private CacheItemPoolInterface $cache)
    {
    }

    public function __invoke(ClearCache $message): void
    {
        $this->cache->clear();
    }
}
