<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\User;
use App\Twig\RecoveryExtension;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class RecoveryExtensionTest extends TestCase
{
    public function testReturnsNullWhenNotLoggedIn(): void
    {
        self::assertNull($this->createExtension(null)->activeRecoveryStartTime());
    }

    public function testReturnsNullWithoutRecoveryProcess(): void
    {
        self::assertNull($this->createExtension(new User('user@example.org'))->activeRecoveryStartTime());
    }

    public function testReturnsStartTimeOfRunningRecoveryProcess(): void
    {
        $startTime = new DateTimeImmutable('-1 hour');
        $user = new User('user@example.org');
        $user->setRecoveryStartTime($startTime);

        self::assertSame($startTime, $this->createExtension($user)->activeRecoveryStartTime());
    }

    public function testReturnsNullForExpiredRecoveryProcess(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('-31 days'));

        self::assertNull($this->createExtension($user)->activeRecoveryStartTime());
    }

    private function createExtension(?User $user): RecoveryExtension
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return new RecoveryExtension($security);
    }
}
