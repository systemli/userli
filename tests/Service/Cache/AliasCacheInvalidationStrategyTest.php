<?php

declare(strict_types=1);

namespace App\Tests\Service\Cache;

use App\Enum\AliasCacheKey;
use App\Enum\UserCacheKey;
use App\Repository\AliasRepository;
use App\Service\Cache\AliasCacheInvalidationStrategy;
use App\Service\Cache\EntityCacheType;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class AliasCacheInvalidationStrategyTest extends TestCase
{
    public function testTypeIsAlias(): void
    {
        $strategy = new AliasCacheInvalidationStrategy(
            $this->createStub(CacheInterface::class),
            $this->createStub(AliasRepository::class),
        );

        self::assertSame(EntityCacheType::ALIAS, $strategy->type());
    }

    public function testInvalidateDropsAliasUserQuotaAndDestinationSenderKeys(): void
    {
        $source = 'alias@example.org';
        $destinations = ['user1@example.org', 'user2@example.org'];

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findDestinationsBySource')
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
            ->with(self::callback(static function (string $key) use (&$expectedKeys): bool {
                $index = array_search($key, $expectedKeys, true);
                if ($index === false) {
                    return false;
                }
                unset($expectedKeys[$index]);

                return true;
            }))
            ->willReturn(true);

        $strategy = new AliasCacheInvalidationStrategy($cache, $aliasRepository);
        $strategy->invalidate($source);

        self::assertEmpty($expectedKeys, 'All expected cache keys should have been deleted');
    }

    public function testInvalidateWithNoDestinations(): void
    {
        $source = 'alias@example.org';

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findDestinationsBySource')
            ->willReturn([]);

        $expectedKeys = [
            AliasCacheKey::POSTFIX_ALIAS->key($source),
            UserCacheKey::POSTFIX_QUOTA->key($source),
        ];

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::exactly(2))
            ->method('delete')
            ->with(self::callback(static function (string $key) use (&$expectedKeys): bool {
                $index = array_search($key, $expectedKeys, true);
                if ($index === false) {
                    return false;
                }
                unset($expectedKeys[$index]);

                return true;
            }))
            ->willReturn(true);

        $strategy = new AliasCacheInvalidationStrategy($cache, $aliasRepository);
        $strategy->invalidate($source);

        self::assertEmpty($expectedKeys, 'All expected cache keys should have been deleted');
    }
}
