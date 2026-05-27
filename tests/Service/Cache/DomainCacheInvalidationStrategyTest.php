<?php

declare(strict_types=1);

namespace App\Tests\Service\Cache;

use App\Enum\DomainCacheKey;
use App\Service\Cache\DomainCacheInvalidationStrategy;
use App\Service\Cache\EntityCacheType;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class DomainCacheInvalidationStrategyTest extends TestCase
{
    public function testTypeIsDomain(): void
    {
        $strategy = new DomainCacheInvalidationStrategy($this->createStub(CacheInterface::class));

        self::assertSame(EntityCacheType::DOMAIN, $strategy->type());
    }

    public function testInvalidateDropsAllDomainCacheKeys(): void
    {
        $name = 'example.org';
        $expectedKeys = DomainCacheKey::allKeysForName($name);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::exactly(\count($expectedKeys)))
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

        $strategy = new DomainCacheInvalidationStrategy($cache);
        $strategy->invalidate($name);

        self::assertEmpty($expectedKeys, 'All expected domain cache keys should have been deleted');
    }
}
