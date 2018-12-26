<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\RecoveryTokenHandler;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class RecoveryTokenHandlerTest extends TestCase
{
    protected function createHandler()
    {
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->any())->method('flush')->willReturn(true);

        $encoderFactory = $this->getMockBuilder(EncoderFactoryInterface::class)
            ->disableOriginalConstructor()->getMock();

        return new RecoveryTokenHandler($objectManager, $encoderFactory);
    }

    public function testCreateUpdateVerifyToken()
    {
        // Test create and verify

        $handler = $this->createHandler();
        $plainPassword = 'password';

        $user = new User();
        $user->setPassword($plainPassword);

        $user->setPlainPassword($plainPassword);
        $recoveryToken = $handler->create($user);

        self::assertTrue($handler->verify($user, $recoveryToken));
        self::assertFalse($handler->verify($user, 'brokenToken'));

        // Test update with stored public key and verify

        $plainPassword = 'password_new';

        $user->setPlainPassword($plainPassword);
        $handler->update($user);

        self::assertTrue($handler->verify($user, $recoveryToken));
        self::assertFalse($handler->verify($user, 'brokenToken'));
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
        $cipher = $user->getRecoverySecret();

        $user->setPlainPassword('password');
        $handler->update($user);
        $cipherNew = $user->getRecoverySecret();

        self::assertNotEquals($cipher, $cipherNew);
    }
}
