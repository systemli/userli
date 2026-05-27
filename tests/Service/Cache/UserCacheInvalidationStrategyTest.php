<?php

declare(strict_types=1);

namespace App\Tests\Service\Cache;

use App\Enum\UserCacheKey;
use App\Service\Cache\EntityCacheType;
use App\Service\Cache\UserCacheInvalidationStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class UserCacheInvalidationStrategyTest extends TestCase
{
    public function testTypeIsUser(): void
    {
        $strategy = new UserCacheInvalidationStrategy($this->createStub(CacheInterface::class));

        self::assertSame(EntityCacheType::USER, $strategy->type());
    }

    public function testInvalidateDropsAllUserCacheKeys(): void
    {
        $email = 'user@example.org';
        $expectedKeys = UserCacheKey::allKeysForEmail($email);

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

        $strategy = new UserCacheInvalidationStrategy($cache);
        $strategy->invalidate($email);

        self::assertEmpty($expectedKeys, 'All expected user cache keys should have been deleted');
    }
}
