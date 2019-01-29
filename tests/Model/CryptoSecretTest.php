<?php

namespace App\Tests\Model;

use App\Model\CryptoSecret;
use PHPUnit\Framework\TestCase;

class CryptoSecretTest extends TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Base64 decoding of encrypted message failed
     */
    public function testDecodeExceptionBase64()
    {
        $secret = new CryptoSecret('', '', '');
        $secret->decode('brokenbase64%%%');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The encrypted message was truncated
     */
    public function testDecodeExceptionTruncated()
    {
        $secret = new CryptoSecret('', '', '');
        $secret->decode('shortcipher');
    }
}
