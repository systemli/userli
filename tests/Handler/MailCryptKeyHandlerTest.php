<?php

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
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock();

        return new MailCryptKeyHandler($manager);
    }

    public function testToPkcs8ExceptionOpenSSLFailed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transforming key to PKCS#8 with OpenSSL failed. OpenSSL exited unsuccessfully:');
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

    public function testCreateExceptionNullPassword(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('plainPassword should not be null');
        $handler = $this->createHandler();
        $user = new User();
        $handler->create($user);
    }

    public function testCreate(): void
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);

        self::assertNotEmpty($user->getMailCryptPublicKey());
        self::assertNotEmpty($user->getMailCryptSecretBox());
        self::assertNotEmpty($user->getPlainMailCryptPrivateKey());
    }

    public function testUpdateExceptionNullSecret(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('secret should not be null');
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->update($user, 'old_password');
    }

    public function testUpdateExceptionDecryptionFailed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('decryption of mailCryptSecretBox failed');
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);

        $handler->update($user, 'wrong_password');
    }

    public function testUpdate(): void
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);
        $secret = $user->getMailCryptSecretBox();

        $user->setPlainPassword('new_password');
        $handler->update($user, 'password');

        self::assertNotEquals($secret, $user->getMailCryptSecretBox());
        self::assertNotEmpty($handler->decrypt($user, 'new_password'));
    }

    public function testUpdateWithPrivateKeyExceptionNullPassword(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('plainPassword should not be null');
        $handler = $this->createHandler();
        $user = new User();
        $handler->updateWithPrivateKey($user, 'old_password');
    }

    public function testUpdateWithPrivateKey(): void
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);
        $secret = $user->getMailCryptSecretBox();

        $user->setPlainPassword('new_password');
        $handler->updateWithPrivateKey($user, 'plain_private_key');

        self::assertNotEquals($secret, $user->getMailCryptSecretBox());
        self::assertNotEmpty($handler->decrypt($user, 'new_password'));
    }

    public function testDecryptExceptionNullSecret(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('secret should not be null');
        $handler = $this->createHandler();
        $user = new User();

        $handler->decrypt($user, 'password');
    }

    public function testDecryptExceptionDecryptionFailed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('decryption of mailCryptSecretBox failed');
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);

        $handler->decrypt($user, 'wrong_password');
    }

    public function testDecrypt(): void
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);

        self::assertNotNull($handler->decrypt($user, 'password'));
    }
}
