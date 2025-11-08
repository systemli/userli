<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Entity\User;
use App\Helper\PasswordUpdater;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;

class PasswordUpdaterTest extends TestCase
{
    public function testUpdatePassword(): void
    {
        $hasher = new PlaintextPasswordHasher();
        $passwordHasherFactory = $this->getMockBuilder(PasswordHasherFactoryInterface::class)
            ->getMock();
        $passwordHasherFactory->method('getPasswordHasher')->willReturn($hasher);
        $updater = new PasswordUpdater($passwordHasherFactory);

        $user = new User();
        $updater->updatePassword($user, 'password');

        $password = $user->getPassword();
        self::assertNotNull($password);

        $updater->updatePassword($user, 'new password');

        self::assertNotEquals($password, $user->getPassword());
    }
}
