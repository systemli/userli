<?php

namespace App\Tests\Enum;

use App\Enum\MailCrypt;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MailCryptTest extends TestCase
{
    public function testFromString()
    {
        $this->assertSame(MailCrypt::DISABLED, MailCrypt::fromString('0'));
        $this->assertSame(MailCrypt::ENABLED_OPTIONAL, MailCrypt::fromString('1'));
        $this->assertSame(MailCrypt::ENABLED_ENFORCE_NEW_USERS, MailCrypt::fromString('2'));
        $this->assertSame(MailCrypt::ENABLED_ENFORCE_ALL_USERS, MailCrypt::fromString('3'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid MailCrypt value: invalid");
        MailCrypt::fromString('invalid');
    }

    public function testIsAtLeast()
    {
        $this->assertTrue(MailCrypt::ENABLED_OPTIONAL->isAtLeast(MailCrypt::DISABLED));
        $this->assertTrue(MailCrypt::ENABLED_ENFORCE_NEW_USERS->isAtLeast(MailCrypt::ENABLED_OPTIONAL));
        $this->assertTrue(MailCrypt::ENABLED_ENFORCE_ALL_USERS->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS));

        $this->assertFalse(MailCrypt::DISABLED->isAtLeast(MailCrypt::ENABLED_OPTIONAL));
        $this->assertFalse(MailCrypt::ENABLED_OPTIONAL->isAtLeast(MailCrypt::ENABLED_ENFORCE_ALL_USERS));
    }
}

