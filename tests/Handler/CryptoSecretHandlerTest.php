<?php

namespace App\Tests\Handler;

use App\Handler\CryptoSecretHandler;
use App\Model\CryptoSecret;
use Exception;
use PHPUnit\Framework\TestCase;

class CryptoSecretHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $secret = CryptoSecretHandler::create('message', 'password');

        self::assertInstanceOf(CryptoSecret::class, $secret);
    }

    public function testDecryptExceptionNullSalt(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("salt should not be null");
        $secret = CryptoSecretHandler::create('message', 'password');

        $secret->setSalt(null);
        CryptoSecretHandler::decrypt($secret, 'password');
    }

    public function testDecrypt(): void
    {
        $secret = CryptoSecretHandler::create('message', 'password');

        self::assertNull(CryptoSecretHandler::decrypt($secret, 'wrong_password'));
        self::assertEquals('message', CryptoSecretHandler::decrypt($secret, 'password'));
    }
}
