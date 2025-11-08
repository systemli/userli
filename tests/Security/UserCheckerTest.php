<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;

class UserCheckerTest extends TestCase
{
    private UserChecker $userChecker;

    protected function setUp(): void
    {
        $this->userChecker = new UserChecker();
    }

    public function testPreAuth(): void
    {
        $user = new User();

        $this->userChecker->checkPreAuth($user);

        $deletedUser = new User();
        $deletedUser->setDeleted(true);

        $this->expectException('Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException');
        $this->userChecker->checkPreAuth($deletedUser);
    }

    public function testPostAuth(): void
    {
        $user = new User();

        $this->userChecker->checkPostAuth($user);

        $this->expectNotToPerformAssertions();
    }
}
