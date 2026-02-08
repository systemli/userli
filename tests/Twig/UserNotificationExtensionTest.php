<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Twig\UserNotificationExtension;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class UserNotificationExtensionTest extends TestCase
{
    public function testHasNotificationsReturnsFalseWhenNotLoggedIn(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $em = $this->createStub(EntityManagerInterface::class);

        $extension = new UserNotificationExtension($security, $em);

        self::assertFalse($extension->hasNotifications());
    }

    public function testHasNotificationsReturnsTrueWhenNotificationsExist(): void
    {
        $user = new User('user@example.org');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $notification = $this->createStub(UserNotification::class);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')
            ->with(['user' => $user])
            ->willReturn([$notification]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $extension = new UserNotificationExtension($security, $em);

        self::assertTrue($extension->hasNotifications());
    }

    public function testHasNotificationsReturnsFalseWhenNoNotifications(): void
    {
        $user = new User('user@example.org');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')
            ->with(['user' => $user])
            ->willReturn([]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $extension = new UserNotificationExtension($security, $em);

        self::assertFalse($extension->hasNotifications());
    }

    public function testHasNotificationsWithTypeFilter(): void
    {
        $user = new User('user@example.org');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $notification = $this->createStub(UserNotification::class);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')
            ->with(['user' => $user, 'type' => 'compromised_password'])
            ->willReturn([$notification]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $extension = new UserNotificationExtension($security, $em);

        self::assertTrue($extension->hasNotifications('compromised_password'));
    }

    public function testHasNotificationsWithTypeFilterReturnsFlaseWhenEmpty(): void
    {
        $user = new User('user@example.org');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')
            ->with(['user' => $user, 'type' => 'compromised_password'])
            ->willReturn([]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $extension = new UserNotificationExtension($security, $em);

        self::assertFalse($extension->hasNotifications('compromised_password'));
    }
}
