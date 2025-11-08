<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\MailCryptKeyPair;
use PHPUnit\Framework\TestCase;

class MailCryptKeyPairTest extends TestCase
{
    public function testEraseKeys(): void
    {
        $keyPair = new MailCryptKeyPair('private', 'public');

        self::assertEquals('private', $keyPair->getPrivateKey());
        self::assertEquals('public', $keyPair->getPublicKey());

        $keyPair->erase();

        self::assertNull($keyPair->getPrivateKey());
        self::assertNull($keyPair->getPublicKey());
    }
}
