<?php

namespace App\Tests\Helper;

use App\Entity\User;
use App\Helper\PasswordUpdater;
use App\Security\Encoder\PasswordHashEncoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class PasswordUpdaterTest extends TestCase
{
    public function testUpdatePassword(): void
    {
        $hasher = $this->getMockBuilder(PasswordHasherInterface::class)
            ->getMock();
        $passwordHasherFactory = $this->getMockBuilder(PasswordHasherFactoryInterface::class)
            ->getMock();
        $passwordHasherFactory->method('getPasswordHasher')->willReturn($hasher);
        $updater = new PasswordUpdater($passwordHasherFactory);

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
