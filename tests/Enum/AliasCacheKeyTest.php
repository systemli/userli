<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\AliasCacheKey;
use PHPUnit\Framework\TestCase;

class AliasCacheKeyTest extends TestCase
{
    public function testKey(): void
    {
        $source = 'alias@example.org';

        self::assertSame('postfix_alias_'.sha1($source), AliasCacheKey::POSTFIX_ALIAS->key($source));
    }

    public function testAllKeysForSource(): void
    {
        $source = 'alias@example.org';
        $keys = AliasCacheKey::allKeysForSource($source);

        self::assertCount(1, $keys);
        self::assertContains('postfix_alias_'.sha1($source), $keys);
    }

    public function testTtl(): void
    {
        self::assertSame(86400, AliasCacheKey::TTL);
        self::assertSame(AliasCacheKey::TTL, AliasCacheKey::POSTFIX_ALIAS->ttl());
    }
}
