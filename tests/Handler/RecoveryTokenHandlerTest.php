<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\RecoveryTokenHandler;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use PHPUnit\Framework\TestCase;

class RecoveryTokenHandlerTest extends TestCase
{
    protected function createHandler(): RecoveryTokenHandler
    {
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->any())->method('flush')->willReturn(true);

        return new RecoveryTokenHandler($objectManager);
    }

    public function testCreateExceptionPlainPasswordNull(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("plainPassword should not be null");
        $handler = $this->createHandler();
        $user = new User();

        $handler->create($user);
    }

    public function testCreateExceptionPlainMailCryptPrivateKeyNull(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("plainMailCryptPrivateKey should not be null");
        $handler = $this->createHandler();
        $user = new User();

        $user->setPlainPassword('password');
        $handler->create($user);
    }

    public function testCreate(): void
    {
        $handler = $this->createHandler();
        $user = new User();

        $user->setPlainPassword('password');
        $user->setPlainMailCryptPrivateKey('dummyKey');
        $handler->create($user);

        self::assertNotEmpty($user->getPlainRecoveryToken());
    }

    public function testVerify(): void
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

        $user->setRecoverySecretBox('brokenSecret');
        self::assertFalse($handler->verify($user, $recoveryToken));
    }

    public function testDecryptExceptionSecretNull(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("secret should not be null");
        $handler = $this->createHandler();
        $user = new User();

        $handler->decrypt($user, 'recoveryToken');
    }

    public function testDecryptExceptionDecryptionFailed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("decryption of recoverySecretBox failed");
        $handler = $this->createHandler();
        $user = new User();
        $user->setPlainPassword('password');
        $user->setPlainMailCryptPrivateKey('privateKey');
        $handler->create($user);

        $handler->decrypt($user, 'brokenRecoveryToken');
    }

    public function testDecrypt(): void
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
