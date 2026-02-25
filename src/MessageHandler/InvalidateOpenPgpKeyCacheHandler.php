<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\OpenPgpKeyCacheKey;
use App\Message\InvalidateOpenPgpKeyCache;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
final readonly class InvalidateOpenPgpKeyCacheHandler
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function __invoke(InvalidateOpenPgpKeyCache $message): void
    {
        foreach (OpenPgpKeyCacheKey::allKeysForEmail($message->email) as $key) {
            $this->cache->delete($key);
        }
    }
}
