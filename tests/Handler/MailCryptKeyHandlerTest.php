<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class MailCryptKeyHandlerTest extends TestCase
{
    private function createHandler()
    {
        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        return new MailCryptKeyHandler($manager);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Transforming key to PKCS#8 with OpenSSL failed. OpenSSL exited unsuccessfully: unable to load key
     */
    public function testToPkcs8ExceptionOpenSSLFailed()
    {
        $handler = $this->createHandler();
        $privateKey = 'brokenKey';
        $handler->toPkcs8($privateKey);
    }

    public function testToPkcs8()
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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage plainPassword should not be null
     */
    public function testCreateExceptionNullPassword()
    {
        $handler = $this->createHandler();
        $user = new User();
        $handler->create($user);
    }

    public function testCreate()
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);

        self::assertNotEmpty($user->getMailCryptPublicKey());
        self::assertNotEmpty($user->getMailCryptSecretBox());
        self::assertNotEmpty($user->getPlainMailCryptPrivateKey());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage secret should not be null
     */
    public function testUpdateExceptionNullSecret()
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->update($user, 'old_password');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage decryption of mailCryptSecretBox failed
     */
    public function testUpdateExceptionDecryptionFailed()
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);

        $handler->update($user, 'wrong_password');
    }

    public function testUpdate()
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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage plainPassword should not be null
     */
    public function testUpdateWithPrivateKeyExceptionNullPassword()
    {
        $handler = $this->createHandler();
        $user = new User();
        $handler->updateWithPrivateKey($user, 'old_password');
    }

    public function testUpdateWithPrivateKey()
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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage secret should not be null
     */
    public function testDecryptExceptionNullSecret()
    {
        $handler = $this->createHandler();
        $user = new User();

        $handler->decrypt($user, 'password');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage decryption of mailCryptSecretBox failed
     */
    public function testDecryptExceptionDecryptionFailed()
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);

        $handler->decrypt($user, 'wrong_password');
    }

    public function testDecrypt()
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);

        self::assertNotNull($handler->decrypt($user, 'password'));
    }
}
