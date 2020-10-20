<?php

namespace App\Tests\Helper;

use App\Entity\User;
use App\Helper\PasswordUpdater;
use App\Security\Encoder\PasswordHashEncoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class PasswordUpdaterTest extends TestCase
{
    public function testUpdatePassword(): void
    {
        $encoderFactory = $this->getMockBuilder(EncoderFactoryInterface::class)
            ->getMock();
        $encoderFactory->method('getEncoder')->willReturn(new PasswordHashEncoder());
        $updater = new PasswordUpdater($encoderFactory);

        $user = new User();
        $user->setPlainPassword('password');

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
