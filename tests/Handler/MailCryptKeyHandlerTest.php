<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;

class MailCryptKeyHandlerTest extends TestCase
{
    private function createHandler(): MailCryptKeyHandler
    {
        $manager = $this->createStub(EntityManagerInterface::class);

        return new MailCryptKeyHandler($manager);
    }

    public function testToPkcs8ExceptionOpenSSLFailed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(MailCryptKeyHandler::MESSAGE_OPENSSL_EXITED_UNSUCCESSFULLY);
        $handler = $this->createHandler();
        $privateKey = 'brokenKey';
        $handler->toPkcs8($privateKey);
    }

    public function testToPkcs8(): void
    {
        $handler = $this->createHandler();
        $privateKey = '-----BEGIN PRIVATE KEY-----
MIHuAgEAMBAGByqGSM49AgEGBSuBBAAjBIHWMIHTAgEBBEIAa4qR1Piudflk83H4
7IWtnstO4B3ZCKdUhFM0AezKqG6+6O1twrIG/jkyv0fo5e6PX0mUKWHv68bLgQJ5
7QB+bl2hgYkDgYYABABI4CbKXvsVOCCA2B0K0FYANYBKThdSsu0XNWfSGoUKEtxo
rBwSl9vwP07FpF2sTe9tVpbBIw8VPjLTJcS12Me+ygFZUnJRHuebAq+0ANkJ9rMw
CdopzBsl2M8eQEw4S7yNMnC+Za7wS0+khKiW0zr6V/tzATnh9mJHcIa9u2iJFxSq
UQ==
-----END PRIVATE KEY-----';
        $privateKeyPkcs8 = $handler->toPkcs8($privateKey);

        self::assertNotEmpty($privateKeyPkcs8);
        self::assertStringStartsWith('-----BEGIN PRIVATE KEY-----', $privateKeyPkcs8);
    }

    public function testCreate(): void
    {
        $handler = $this->createHandler();
        $user = new User('test@example.org');
        $handler->create($user, 'password');

        self::assertNotEmpty($user->getMailCryptPublicKey());
        self::assertNotEmpty($user->getMailCryptSecretBox());
        self::assertNotEmpty($user->getPlainMailCryptPrivateKey());
    }

    public function testUpdateExceptionNullSecret(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(MailCryptKeyHandler::MESSAGE_SECRET_IS_NULL);
        $handler = $this->createHandler();
        $user = new User('test@example.org');
        $handler->update($user, 'oldPassword', 'newPassword');
    }

    public function testUpdateExceptionDecryptionFailed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(MailCryptKeyHandler::MESSAGE_DECRYPTION_FAILED);
        $handler = $this->createHandler();
        $user = new User('test@example.org');
        $handler->create($user, 'password');

        $handler->update($user, 'wrongPassword', 'newPassword');
    }

    public function testUpdate(): void
    {
        $handler = $this->createHandler();
        $user = new User('test@example.org');
        $handler->create($user, 'password');
        $secret = $user->getMailCryptSecretBox();

        $handler->update($user, 'password', 'newPassword');

        self::assertNotEquals($secret, $user->getMailCryptSecretBox());
        self::assertNotEmpty($handler->decrypt($user, 'newPassword'));
    }

    public function testUpdateWithPrivateKey(): void
    {
        $handler = $this->createHandler();
        $user = new User('test@example.org');
        $handler->create($user, 'password');
        $secret = $user->getMailCryptSecretBox();

        $handler->updateWithPrivateKey($user, $secret, 'new_password');

        self::assertNotEquals($secret, $user->getMailCryptSecretBox());
        self::assertNotEmpty($handler->decrypt($user, 'new_password'));
    }

    public function testDecryptExceptionNullSecret(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(MailCryptKeyHandler::MESSAGE_SECRET_IS_NULL);
        $handler = $this->createHandler();
        $user = new User('test@example.org');

        $handler->decrypt($user, 'password');
    }

    public function testDecryptExceptionDecryptionFailed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(MailCryptKeyHandler::MESSAGE_DECRYPTION_FAILED);
        $handler = $this->createHandler();
        $user = new User('test@example.org');
        $handler->create($user, 'password');
        $handler->decrypt($user, 'wrong_password');
    }

    public function testDecrypt(): void
    {
        $handler = $this->createHandler();
        $user = new User('test@example.org');
        $handler->create($user, 'password');

        self::assertNotNull($handler->decrypt($user, 'password'));
    }
}
