<?php

namespace App\Tests\Model;

use App\Model\CryptoBoxSecret;
use PHPUnit\Framework\TestCase;

class CryptoBoxSecretTest extends TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Base64 decoding of encrypted message failed
     */
    public function testDecodeExceptionBase64()
    {
        $secret = new CryptoBoxSecret('', '', '');
        $secret->decode('brokenbase64%%%');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The encrypted message was truncated
     */
    public function testDecodeExceptionTruncated()
    {
        $secret = new CryptoBoxSecret('', '', '');
        $secret->decode('shortcipher');
    }
}
