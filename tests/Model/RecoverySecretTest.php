<?php
/**
 * Created by PhpStorm.
 * User: resivo
 * Date: 03.01.19
 * Time: 16:15
 */

namespace App\Tests\Model;

use App\Model\RecoverySecret;
use PHPUnit\Framework\TestCase;

class RecoverySecretTest extends TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Base64 decoding of encrypted message failed
     */
    public function testDecodeExceptionBase64()
    {
        $secret = new RecoverySecret('', '', '');
        $secret->decode('brokenbase64%%%');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The encrypted message was truncated
     */
    public function testDecodeExceptionTruncated()
    {
        $secret = new RecoverySecret('', '', '');
        $secret->decode('shortcipher');
    }
}
