<?php

namespace App\Tests\Model;

use App\Model\MailCryptKeyPair;
use PHPUnit\Framework\TestCase;

class MailCryptKeyPairTest extends TestCase
{
    public function testEraseKeys()
    {
        $keyPair = new MailCryptKeyPair('private', 'public');

        self::assertEquals('private', $keyPair->getPrivateKey());
        self::assertEquals('public', $keyPair->getPublicKey());

        $keyPair->erase();

        self::assertNull($keyPair->getPrivateKey());
        self::assertNull($keyPair->getPublicKey());
    }
}
