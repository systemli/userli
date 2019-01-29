<?php

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\RecoveryTokenHandler;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class RecoveryTokenHandlerTest extends TestCase
{
    protected function createHandler()
    {
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->any())->method('flush')->willReturn(true);

        $encoder = $this->getMockBuilder(PasswordEncoderInterface::class)->getMock();
        $encoder->expects($this->any())->method('isPasswordValid')->willReturn(true);

        $encoderFactory = $this->getMockBuilder(EncoderFactory::class)
            ->disableOriginalConstructor()->getMock();
        $encoderFactory->expects($this->any())->method('getEncoder')->willReturn($encoder);

        return new RecoveryTokenHandler($objectManager, $encoderFactory);
    }

    public function testCreate()
    {
        $handler = $this->createHandler();
        $user = new User();

        $user->setPlainPassword('password');
        $handler->create($user);

        self::assertNotEmpty($user->getPlainRecoveryToken());
    }

    public function testVerify()
    {
        $handler = $this->createHandler();
        $user = new User();

        self::assertFalse($handler->verify($user, 'recoveryToken'));

        $user->setPlainPassword('password');
        $handler->create($user);

        $recoveryToken = $user->getPlainRecoveryToken();

        self::assertTrue($handler->verify($user, $recoveryToken));
        self::assertFalse($handler->verify($user, 'brokenToken'));

        $user->setRecoverySecret('brokenSecret');
        self::assertFalse($handler->verify($user, $recoveryToken));
    }
}
