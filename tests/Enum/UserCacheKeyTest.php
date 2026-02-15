<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\UserCacheKey;
use PHPUnit\Framework\TestCase;

class UserCacheKeyTest extends TestCase
{
    public function testKey(): void
    {
        $email = 'user@example.org';

        self::assertSame('dovecot_lookup_'.sha1($email), UserCacheKey::DOVECOT_LOOKUP->key($email));
        self::assertSame('postfix_mailbox_'.sha1($email), UserCacheKey::POSTFIX_MAILBOX->key($email));
        self::assertSame('postfix_quota_'.sha1($email), UserCacheKey::POSTFIX_QUOTA->key($email));
        self::assertSame('postfix_senders_'.sha1($email), UserCacheKey::POSTFIX_SENDERS->key($email));
    }

    public function testAllKeysForEmail(): void
    {
        $email = 'user@example.org';
        $keys = UserCacheKey::allKeysForEmail($email);

        self::assertCount(4, $keys);
        self::assertContains('dovecot_lookup_'.sha1($email), $keys);
        self::assertContains('postfix_mailbox_'.sha1($email), $keys);
        self::assertContains('postfix_quota_'.sha1($email), $keys);
        self::assertContains('postfix_senders_'.sha1($email), $keys);
    }

    public function testTtl(): void
    {
        self::assertSame(86400, UserCacheKey::TTL);
        self::assertSame(UserCacheKey::TTL, UserCacheKey::DOVECOT_LOOKUP->ttl());
        self::assertSame(UserCacheKey::TTL, UserCacheKey::POSTFIX_MAILBOX->ttl());
        self::assertSame(UserCacheKey::TTL, UserCacheKey::POSTFIX_QUOTA->ttl());
        self::assertSame(UserCacheKey::TTL, UserCacheKey::POSTFIX_SENDERS->ttl());
    }
}
