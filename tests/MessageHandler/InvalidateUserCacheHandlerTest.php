<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Enum\UserCacheKey;
use App\Message\InvalidateUserCache;
use App\MessageHandler\InvalidateUserCacheHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class InvalidateUserCacheHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $email = 'user@example.org';
        $expectedKeys = UserCacheKey::allKeysForEmail($email);

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

        $handler = new InvalidateUserCacheHandler($cache);
        $handler(new InvalidateUserCache($email));

        self::assertEmpty($expectedKeys, 'All expected cache keys should have been deleted');
    }
}
