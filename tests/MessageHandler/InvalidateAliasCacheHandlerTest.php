<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Enum\AliasCacheKey;
use App\Enum\UserCacheKey;
use App\Message\InvalidateAliasCache;
use App\MessageHandler\InvalidateAliasCacheHandler;
use App\Repository\AliasRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class InvalidateAliasCacheHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $source = 'alias@example.org';
        $destinations = ['user1@example.org', 'user2@example.org'];

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findDestinationsBySource')
            ->with($source)
            ->willReturn($destinations);

        $expectedKeys = [
            AliasCacheKey::POSTFIX_ALIAS->key($source),
            UserCacheKey::POSTFIX_QUOTA->key($source),
            UserCacheKey::POSTFIX_SENDERS->key('user1@example.org'),
            UserCacheKey::POSTFIX_SENDERS->key('user2@example.org'),
        ];

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::exactly(4))
            ->method('delete')
            ->with($this->callback(static function (string $key) use (&$expectedKeys): bool {
                $index = array_search($key, $expectedKeys, true);
                if ($index === false) {
                    return false;
                }
                unset($expectedKeys[$index]);

                return true;
            }))
            ->willReturn(true);

        $handler = new InvalidateAliasCacheHandler($cache, $aliasRepository);
        $handler(new InvalidateAliasCache($source));

        self::assertEmpty($expectedKeys, 'All expected cache keys should have been deleted');
    }

    public function testInvokeWithNoDestinations(): void
    {
        $source = 'alias@example.org';

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findDestinationsBySource')
            ->with($source)
            ->willReturn([]);

        $expectedKeys = [
            AliasCacheKey::POSTFIX_ALIAS->key($source),
            UserCacheKey::POSTFIX_QUOTA->key($source),
        ];

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::exactly(2))
            ->method('delete')
            ->with($this->callback(static function (string $key) use (&$expectedKeys): bool {
                $index = array_search($key, $expectedKeys, true);
                if ($index === false) {
                    return false;
                }
                unset($expectedKeys[$index]);

                return true;
            }))
            ->willReturn(true);

        $handler = new InvalidateAliasCacheHandler($cache, $aliasRepository);
        $handler(new InvalidateAliasCache($source));

        self::assertEmpty($expectedKeys, 'All expected cache keys should have been deleted');
    }
}
