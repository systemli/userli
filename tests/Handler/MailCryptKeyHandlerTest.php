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
        self::assertNotEmpty($user->getMailCryptPrivateSecret());
        self::assertNotEmpty($user->getPlainMailCryptPrivateKey());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage plainPassword should not be null
     */
    public function testUpdateExceptionNullPassword()
    {
        $handler = $this->createHandler();
        $user = new User();
        $handler->update($user, 'old_password');
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
     * @expectedExceptionMessage decryption of mailCryptPrivateSecret failed
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
        $secret = $user->getMailCryptPrivateSecret();

        $user->setPlainPassword('new_password');
        $handler->update($user, 'password');

        self::assertNotEquals($secret, $user->getMailCryptPrivateSecret());
        self::assertNotEmpty($handler->decrypt($user, 'new_password'));
    }

    public function testUpdateWithPrivateKey()
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $handler->create($user);
        $secret = $user->getMailCryptPrivateSecret();

        $user->setPlainPassword('new_password');
        $handler->updateWithPrivateKey($user, 'plain_private_key');

        self::assertNotEquals($secret, $user->getMailCryptPrivateSecret());
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
     * @expectedExceptionMessage decryption of mailCryptPrivateSecret failed
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
