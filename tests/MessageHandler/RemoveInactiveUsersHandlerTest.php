<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Enum\Roles;
use App\Handler\DeleteHandler;
use App\Message\RemoveInactiveUsers;
use App\MessageHandler\RemoveInactiveUsersHandler;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveInactiveUsersHandlerTest extends TestCase
{
    public function testDeletesInactiveUsers(): void
    {
        $message = new RemoveInactiveUsers();

        $user1 = $this->createMock(User::class);
        $user1->method('hasRole')->willReturn(false);

        $user2 = $this->createMock(User::class);
        $user2->method('hasRole')->willReturn(false);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findInactiveUsers')
            ->with(2 * 365 + 7)
            ->willReturn([$user1, $user2]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler->expects($this->exactly(2))
            ->method('deleteUser')
            ->withConsecutive([$user1], [$user2]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context): void {
                static $callCount = 0;
                ++$callCount;
                if ($callCount === 1) {
                    $this->assertSame('Found inactive users', $message);
                    $this->assertSame(['count' => 2], $context);
                } else {
                    $this->assertSame('Removed inactive users', $message);
                    $this->assertSame(['deleted' => 2], $context);
                }
            });

        $handler = new RemoveInactiveUsersHandler($em, $deleteHandler, $logger);
        $handler($message);
    }

    public function testSkipsAdminUsers(): void
    {
        $message = new RemoveInactiveUsers();

        $adminUser = $this->createMock(User::class);
        $adminUser->method('hasRole')
            ->willReturnCallback(fn (string $role) => $role === Roles::ADMIN);

        $normalUser = $this->createMock(User::class);
        $normalUser->method('hasRole')->willReturn(false);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findInactiveUsers')
            ->willReturn([$adminUser, $normalUser]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler->expects($this->once())
            ->method('deleteUser')
            ->with($normalUser);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context): void {
                static $callCount = 0;
                ++$callCount;
                if ($callCount === 1) {
                    $this->assertSame('Found inactive users', $message);
                    $this->assertSame(['count' => 2], $context);
                } else {
                    $this->assertSame('Removed inactive users', $message);
                    $this->assertSame(['deleted' => 1], $context);
                }
            });

        $handler = new RemoveInactiveUsersHandler($em, $deleteHandler, $logger);
        $handler($message);
    }

    public function testSkipsDomainAdminUsers(): void
    {
        $message = new RemoveInactiveUsers();

        $domainAdminUser = $this->createMock(User::class);
        $domainAdminUser->method('hasRole')
            ->willReturnCallback(fn (string $role) => $role === Roles::DOMAIN_ADMIN);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findInactiveUsers')
            ->willReturn([$domainAdminUser]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler->expects($this->never())->method('deleteUser');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context): void {
                static $callCount = 0;
                ++$callCount;
                if ($callCount === 1) {
                    $this->assertSame('Found inactive users', $message);
                    $this->assertSame(['count' => 1], $context);
                } else {
                    $this->assertSame('Removed inactive users', $message);
                    $this->assertSame(['deleted' => 0], $context);
                }
            });

        $handler = new RemoveInactiveUsersHandler($em, $deleteHandler, $logger);
        $handler($message);
    }

    public function testSkipsPermanentUsers(): void
    {
        $message = new RemoveInactiveUsers();

        $permanentUser = $this->createMock(User::class);
        $permanentUser->method('hasRole')
            ->willReturnCallback(fn (string $role) => $role === Roles::PERMANENT);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findInactiveUsers')
            ->willReturn([$permanentUser]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler->expects($this->never())->method('deleteUser');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context): void {
                static $callCount = 0;
                ++$callCount;
                if ($callCount === 1) {
                    $this->assertSame('Found inactive users', $message);
                    $this->assertSame(['count' => 1], $context);
                } else {
                    $this->assertSame('Removed inactive users', $message);
                    $this->assertSame(['deleted' => 0], $context);
                }
            });

        $handler = new RemoveInactiveUsersHandler($em, $deleteHandler, $logger);
        $handler($message);
    }

    public function testHandlesEmptyUserList(): void
    {
        $message = new RemoveInactiveUsers();

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findInactiveUsers')
            ->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler->expects($this->never())->method('deleteUser');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context): void {
                static $callCount = 0;
                ++$callCount;
                if ($callCount === 1) {
                    $this->assertSame('Found inactive users', $message);
                    $this->assertSame(['count' => 0], $context);
                } else {
                    $this->assertSame('Removed inactive users', $message);
                    $this->assertSame(['deleted' => 0], $context);
                }
            });

        $handler = new RemoveInactiveUsersHandler($em, $deleteHandler, $logger);
        $handler($message);
    }
}
