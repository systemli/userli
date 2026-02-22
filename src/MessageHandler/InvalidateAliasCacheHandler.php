<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\AliasCacheKey;
use App\Enum\UserCacheKey;
use App\Message\InvalidateAliasCache;
use App\Repository\AliasRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
final readonly class InvalidateAliasCacheHandler
{
    public function __construct(private CacheInterface $cache, private AliasRepository $aliasRepository)
    {
    }

    public function __invoke(InvalidateAliasCache $message): void
    {
        foreach (AliasCacheKey::allKeysForSource($message->source) as $key) {
            $this->cache->delete($key);
        }

        // Invalidate quota cache for the alias source address
        $this->cache->delete(UserCacheKey::POSTFIX_QUOTA->key($message->source));

        // Invalidate sender cache for all destination emails of the alias
        $emails = $this->aliasRepository->findDestinationsBySource($message->source);
        foreach ($emails as $email) {
            $this->cache->delete(UserCacheKey::POSTFIX_SENDERS->key($email));
        }
    }
}
