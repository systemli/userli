<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Enum\OpenPgpKeyCacheKey;
use App\Message\InvalidateOpenPgpKeyCache;
use App\MessageHandler\InvalidateOpenPgpKeyCacheHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class InvalidateOpenPgpKeyCacheHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $email = 'alice@example.org';
        $expectedKeys = OpenPgpKeyCacheKey::allKeysForEmail($email);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::exactly(count($expectedKeys)))
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

        $handler = new InvalidateOpenPgpKeyCacheHandler($cache);
        $handler(new InvalidateOpenPgpKeyCache($email));

        self::assertEmpty($expectedKeys, 'All expected cache keys should have been deleted');
    }
}
