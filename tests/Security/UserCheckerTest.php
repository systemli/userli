<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    public function testCheckPreAuth(): void
    {
        $user = new User();
        $user->setDeleted(true);

        $checker = new UserChecker();
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Your user account is deleted.');
        $checker->checkPreAuth($user);

        $user = $this->getMockBuilder(UserInterface::class)
            ->getMock();

        $checker->checkPreAuth($user);
    }
    public function testCheckPostAuth(): void
    {
        $user = new User();
        $user->setEnabled(false);

        $checker = new UserChecker();
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Your user account is disabled.');
        $checker->checkPostAuth($user);

        $user = $this->getMockBuilder(UserInterface::class)
            ->getMock();

        $checker->checkPreAuth($user);
    }
}
