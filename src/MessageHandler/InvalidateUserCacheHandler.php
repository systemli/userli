<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\UserCacheKey;
use App\Message\InvalidateUserCache;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
final readonly class InvalidateUserCacheHandler
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function __invoke(InvalidateUserCache $message): void
    {
        foreach (UserCacheKey::allKeysForEmail($message->email) as $key) {
            $this->cache->delete($key);
        }
    }
}
