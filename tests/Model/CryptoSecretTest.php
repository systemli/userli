<?php

namespace App\Tests\Model;

use App\Model\CryptoSecret;
use Exception;
use PHPUnit\Framework\TestCase;

class CryptoSecretTest extends TestCase
{
    public function testDecodeExceptionBase64(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Base64 decoding of encrypted message failed');
        $secret = new CryptoSecret('', '', '');
        $secret::decode('brokenbase64%%%');
    }

    public function testDecodeExceptionTruncated(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The encrypted message was truncated');
        $secret = new CryptoSecret('', '', '');
        $secret::decode('shortcipher');
    }
}
