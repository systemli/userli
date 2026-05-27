<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Message\InvalidateEntityCache;
use App\MessageHandler\InvalidateEntityCacheHandler;
use App\Service\Cache\EntityCacheType;
use App\Service\Cache\InvalidationStrategy;
use PHPUnit\Framework\TestCase;

class InvalidateEntityCacheHandlerTest extends TestCase
{
    public function testHandlerRoutesToMatchingStrategy(): void
    {
        $identifier = 'user@example.org';

        $userStrategy = $this->createMock(InvalidationStrategy::class);
        $userStrategy->method('type')->willReturn(EntityCacheType::USER);
        $userStrategy->expects(self::once())->method('invalidate')->with($identifier);

        $aliasStrategy = $this->createMock(InvalidationStrategy::class);
        $aliasStrategy->method('type')->willReturn(EntityCacheType::ALIAS);
        $aliasStrategy->expects(self::never())->method('invalidate');

        $handler = new InvalidateEntityCacheHandler([$userStrategy, $aliasStrategy]);
        $handler(new InvalidateEntityCache(EntityCacheType::USER, $identifier));
    }

    public function testHandlerIsNoOpWhenNoStrategyMatches(): void
    {
        $strategy = $this->createMock(InvalidationStrategy::class);
        $strategy->method('type')->willReturn(EntityCacheType::USER);
        $strategy->expects(self::never())->method('invalidate');

        $handler = new InvalidateEntityCacheHandler([$strategy]);
        $handler(new InvalidateEntityCache(EntityCacheType::ALIAS, 'alias@example.org'));
    }
}
