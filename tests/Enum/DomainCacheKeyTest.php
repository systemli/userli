<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\DomainCacheKey;
use PHPUnit\Framework\TestCase;

class DomainCacheKeyTest extends TestCase
{
    public function testKey(): void
    {
        $name = 'example.org';

        self::assertSame('postfix_domain_'.sha1($name), DomainCacheKey::POSTFIX_DOMAIN->key($name));
    }

    public function testAllKeysForName(): void
    {
        $name = 'example.org';
        $keys = DomainCacheKey::allKeysForName($name);

        self::assertCount(1, $keys);
        self::assertContains('postfix_domain_'.sha1($name), $keys);
    }

    public function testTtl(): void
    {
        self::assertSame(86400, DomainCacheKey::TTL);
        self::assertSame(DomainCacheKey::TTL, DomainCacheKey::POSTFIX_DOMAIN->ttl());
    }
}
