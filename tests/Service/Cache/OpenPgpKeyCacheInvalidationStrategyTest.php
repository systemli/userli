<?php

declare(strict_types=1);

namespace App\Tests\Service\Cache;

use App\Enum\OpenPgpKeyCacheKey;
use App\Service\Cache\EntityCacheType;
use App\Service\Cache\OpenPgpKeyCacheInvalidationStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class OpenPgpKeyCacheInvalidationStrategyTest extends TestCase
{
    public function testTypeIsOpenPgpKey(): void
    {
        $strategy = new OpenPgpKeyCacheInvalidationStrategy($this->createStub(CacheInterface::class));

        self::assertSame(EntityCacheType::OPENPGP_KEY, $strategy->type());
    }

    public function testInvalidateDropsAllOpenPgpKeyCacheKeys(): void
    {
        $email = 'user@example.org';
        $expectedKeys = OpenPgpKeyCacheKey::allKeysForEmail($email);

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

        $strategy = new OpenPgpKeyCacheInvalidationStrategy($cache);
        $strategy->invalidate($email);

        self::assertEmpty($expectedKeys, 'All expected OpenPGP key cache keys should have been deleted');
    }
}
