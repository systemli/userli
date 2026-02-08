<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Enum\MailCrypt;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MailCryptTest extends TestCase
{
    public function testFromString(): void
    {
        self::assertSame(MailCrypt::DISABLED, MailCrypt::fromString('0'));
        self::assertSame(MailCrypt::ENABLED_OPTIONAL, MailCrypt::fromString('1'));
        self::assertSame(MailCrypt::ENABLED_ENFORCE_NEW_USERS, MailCrypt::fromString('2'));
        self::assertSame(MailCrypt::ENABLED_ENFORCE_ALL_USERS, MailCrypt::fromString('3'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid MailCrypt value: invalid');
        MailCrypt::fromString('invalid');
    }

    public function testIsAtLeast(): void
    {
        self::assertTrue(MailCrypt::ENABLED_OPTIONAL->isAtLeast(MailCrypt::DISABLED));
        self::assertTrue(MailCrypt::ENABLED_ENFORCE_NEW_USERS->isAtLeast(MailCrypt::ENABLED_OPTIONAL));
        self::assertTrue(MailCrypt::ENABLED_ENFORCE_ALL_USERS->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS));

        self::assertFalse(MailCrypt::DISABLED->isAtLeast(MailCrypt::ENABLED_OPTIONAL));
        self::assertFalse(MailCrypt::ENABLED_OPTIONAL->isAtLeast(MailCrypt::ENABLED_ENFORCE_ALL_USERS));
    }
}
