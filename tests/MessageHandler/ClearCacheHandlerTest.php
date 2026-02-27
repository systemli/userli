<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Message\ClearCache;
use App\MessageHandler\ClearCacheHandler;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class ClearCacheHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects(self::once())
            ->method('clear')
            ->willReturn(true);

        $handler = new ClearCacheHandler($cache);
        $handler(new ClearCache());
    }
}
