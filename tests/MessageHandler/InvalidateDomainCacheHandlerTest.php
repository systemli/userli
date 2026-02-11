<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Enum\DomainCacheKey;
use App\Message\InvalidateDomainCache;
use App\MessageHandler\InvalidateDomainCacheHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class InvalidateDomainCacheHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $name = 'example.org';

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::once())
            ->method('delete')
            ->with(DomainCacheKey::POSTFIX_DOMAIN->key($name))
            ->willReturn(true);

        $handler = new InvalidateDomainCacheHandler($cache);
        $handler(new InvalidateDomainCache($name));
    }
}
