<?php

namespace App\Tests\Handler;

use App\Handler\CryptoSecretHandler;
use App\Model\CryptoSecret;
use PHPUnit\Framework\TestCase;

class CryptoSecretHandlerTest extends TestCase
{
    public function testCreate()
    {
        $secret = CryptoSecretHandler::create('message', 'password');

        self::assertInstanceOf(CryptoSecret::class, $secret);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage salt should not be null
     */
    public function testDecryptExceptionNullSalt()
    {
        $secret = CryptoSecretHandler::create('message', 'password');

        $secret->setSalt(null);
        CryptoSecretHandler::decrypt($secret, 'password');
    }

    public function testDecrypt()
    {
        $secret = CryptoSecretHandler::create('message', 'password');

        self::assertNull(CryptoSecretHandler::decrypt($secret, 'wrong_password'));
        self::assertEquals('message', CryptoSecretHandler::decrypt($secret, 'password'));
    }
}
