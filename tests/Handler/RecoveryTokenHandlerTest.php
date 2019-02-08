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

    public function testCreate()
    {
        $handler = $this->createHandler();
        $user = new User();

        $user->setPlainPassword('password');
        $user->setPlainMailCryptPrivateKey('dummyKey');
        $handler->create($user);

        self::assertNotEmpty($user->getPlainRecoveryToken());
    }

    public function testVerify()
    {
        $handler = $this->createHandler();
        $user = new User();

        self::assertFalse($handler->verify($user, 'recoveryToken'));

        $user->setPlainPassword('password');
        $user->setPlainMailCryptPrivateKey('dummyKey');
        $handler->create($user);

        $recoveryToken = $user->getPlainRecoveryToken();

        self::assertTrue($handler->verify($user, $recoveryToken));
        self::assertFalse($handler->verify($user, 'brokenToken'));

        $user->setRecoverySecret('brokenSecret');
        self::assertFalse($handler->verify($user, $recoveryToken));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage secret should not be null
     */
    public function testDecryptExceptionNullSecret()
    {
        $handler = $this->createHandler();
        $user = new User();

        $handler->decrypt($user, 'recoveryToken');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage decryption of recoverySecret failed
     */
    public function testDecryptExceptionDecryptionFailed()
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $user->setPlainMailCryptPrivateKey('privateKey');
        $handler->create($user);

        $handler->decrypt($user, 'brokenRecoveryToken');
    }

    public function testDecrypt()
    {
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $user->setPlainMailCryptPrivateKey('privateKey');
        $handler->create($user);
        $recoveryToken = $user->getPlainRecoveryToken();

        self::assertEquals('privateKey', $handler->decrypt($user, $recoveryToken));
    }
}
