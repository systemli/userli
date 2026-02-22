<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\OpenPgpKeyCacheKey;
use PHPUnit\Framework\TestCase;

class OpenPgpKeyCacheKeyTest extends TestCase
{
    public function testKey(): void
    {
        $identifier = 'kei1q4tipxxu1yj79k9kfukdhfy631xe@example.org';

        self::assertSame('wkd_lookup_'.sha1($identifier), OpenPgpKeyCacheKey::WKD_LOOKUP->key($identifier));
    }

    public function testAllKeysForEmail(): void
    {
        $email = 'alice@example.org';
        $keys = OpenPgpKeyCacheKey::allKeysForEmail($email);

        // WKD hash of 'alice' is 'kei1q4tipxxu1yj79k9kfukdhfy631xe'
        $expectedIdentifier = 'kei1q4tipxxu1yj79k9kfukdhfy631xe@example.org';
        self::assertCount(1, $keys);
        self::assertContains('wkd_lookup_'.sha1($expectedIdentifier), $keys);
    }

    public function testAllKeysForEmailNormalizesCase(): void
    {
        $email = 'Alice@Example.ORG';
        $keys = OpenPgpKeyCacheKey::allKeysForEmail($email);

        // WKD hash of 'alice' (lowercased) is 'kei1q4tipxxu1yj79k9kfukdhfy631xe'
        $expectedIdentifier = 'kei1q4tipxxu1yj79k9kfukdhfy631xe@example.org';
        self::assertCount(1, $keys);
        self::assertContains('wkd_lookup_'.sha1($expectedIdentifier), $keys);
    }

    public function testTtl(): void
    {
        self::assertSame(86400, OpenPgpKeyCacheKey::TTL);
        self::assertSame(OpenPgpKeyCacheKey::TTL, OpenPgpKeyCacheKey::WKD_LOOKUP->ttl());
    }
}
