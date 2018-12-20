<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\RecoveryTokenHandler;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class RecoveryTokenHandlerTest extends TestCase
{
    protected function createHandler()
    {
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->any())->method('flush')->willReturn(true);

        return new RecoveryTokenHandler($objectManager);
    }

    public function testTokenEncryptDecrypt()
    {
        $handler = $this->createHandler();

        $plainPassword = 'password';
        $recoveryToken = $handler->tokenGenerate();

        $cipher = $handler->tokenEncrypt($plainPassword, $recoveryToken);
        $message = $handler->tokenDecrypt($cipher, $recoveryToken);

        self::assertEquals($plainPassword, $message);
    }

    public function testTokenCreateUpdateDecrypt()
    {
        // Test creating and decrypting

        $handler = $this->createHandler();
        $plainPassword = 'password';

        $user = new User();
        $user->setPassword($plainPassword);

        $user->setPlainPassword($plainPassword);
        $recoveryToken = $handler->create($user);

        $message = $handler->tokenDecrypt($user->getRecoveryCipher(), $recoveryToken);
        self::assertEquals($plainPassword, $message);

        $message = $handler->tokenDecrypt($user->getRecoveryCipher(), 'brokenToken');
        self::assertNotEquals($plainPassword, $message);

        // Test updating with stored public key and decrypting

        $plainPassword = 'password_new';

        $user->setPlainPassword($plainPassword);
        $handler->update($user);

        $message = $handler->tokenDecrypt($user->getRecoveryCipher(), $recoveryToken);
        self::assertEquals($plainPassword, $message);

        $message = $handler->tokenDecrypt($user->getRecoveryCipher(), 'brokenToken');
        self::assertNotEquals($plainPassword, $message);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Base64 decoding of encrypted message failed
     */
    public function testTokenDecodeExceptionBase64()
    {
        $handler = $this->createHandler();
        $handler->cipherDecode('brokenbase64%%%');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The encrypted message was truncated
     */
    public function testTokenDecodeExceptionTruncated()
    {
        $handler = $this->createHandler();
        $handler->cipherDecode('shortcipher');
    }

    public function testCreate()
    {
        $handler = $this->createHandler();
        $user = new User();

        $user->setPlainPassword('password');
        $recoveryToken = $handler->create($user);

        self::assertNotEmpty($recoveryToken);
    }

    public function testUpdate()
    {
        $handler = $this->createHandler();
        $user = new User();

        $user->setPlainPassword('password');
        $handler->create($user);
        $cipher = $user->getRecoveryCipher();

        $user->setPlainPassword('password');
        $handler->update($user);
        $cipherNew = $user->getRecoveryCipher();

        self::assertNotEquals($cipher, $cipherNew);
    }
}
