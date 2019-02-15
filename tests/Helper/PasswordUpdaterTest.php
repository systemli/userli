<?php

namespace App\Tests\Helper;

use App\Entity\User;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use App\Security\Encoder\PasswordHashEncoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class PasswordUpdaterTest extends TestCase
{
    public function testUpdatePassword()
    {
        $encoderFactory = $this->getMockBuilder(EncoderFactoryInterface::class)
            ->getMock();
        $encoderFactory->expects($this->any())->method('getEncoder')->willReturn(new PasswordHashEncoder());
        $recoveryTokenHandler = $this->getMockBuilder(RecoveryTokenHandler::class)
            ->disableOriginalConstructor()->getMock();
        $recoveryTokenHandler->expects($this->any())->method('update')->willReturn(true);
        $updater = new PasswordUpdater($encoderFactory, $recoveryTokenHandler);

        $user = new User();
        $user->setPlainPassword('password');
        $user->setRecoverySecret('brokenSecret');

        self::assertNull($user->getPassword());

        $updater->updatePassword($user);

        $password = $user->getPassword();
        self::assertNotNull($password);

        $user->setPlainPassword(null);

        $updater->updatePassword($user);

        self::assertEquals($password, $user->getPassword());

        $user->setPlainPassword('');

        $updater->updatePassword($user);

        self::assertEquals($password, $user->getPassword());
    }
}
