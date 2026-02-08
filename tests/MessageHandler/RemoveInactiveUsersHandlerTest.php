<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Enum\Roles;
use App\Message\DeleteUser;
use App\Message\RemoveInactiveUsers;
use App\MessageHandler\RemoveInactiveUsersHandler;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class RemoveInactiveUsersHandlerTest extends TestCase
{
    private array $dispatchedMessages = [];

    public function testDispatchesDeleteUserForInactiveUsers(): void
    {
        $message = new RemoveInactiveUsers();

        $user1 = $this->createStub(User::class);
        $user1->method('hasRole')->willReturn(false);
        $user1->method('getId')->willReturn(1);

        $user2 = $this->createStub(User::class);
        $user2->method('hasRole')->willReturn(false);
        $user2->method('getId')->willReturn(2);

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

        $this->dispatchedMessages = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($message) {
                $this->dispatchedMessages[] = $message;

                return new Envelope($message);
            });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(static function (string $message, array $context): void {
                static $callCount = 0;
                ++$callCount;
                if ($callCount === 1) {
                    self::assertSame('Found inactive users', $message);
                    self::assertSame(['count' => 2], $context);
                } else {
                    self::assertSame('Dispatched user deletions', $message);
                    self::assertSame(['dispatched' => 2], $context);
                }
            });

        $handler = new RemoveInactiveUsersHandler($em, $messageBus, $logger);
        $handler($message);

        self::assertCount(2, $this->dispatchedMessages);
        self::assertInstanceOf(DeleteUser::class, $this->dispatchedMessages[0]);
        self::assertSame(1, $this->dispatchedMessages[0]->userId);
        self::assertInstanceOf(DeleteUser::class, $this->dispatchedMessages[1]);
        self::assertSame(2, $this->dispatchedMessages[1]->userId);
    }

    public function testSkipsAdminUsers(): void
    {
        $message = new RemoveInactiveUsers();

        $adminUser = $this->createStub(User::class);
        $adminUser->method('hasRole')
            ->willReturnCallback(static fn (string $role) => $role === Roles::ADMIN);
        $adminUser->method('getId')->willReturn(1);

        $normalUser = $this->createStub(User::class);
        $normalUser->method('hasRole')->willReturn(false);
        $normalUser->method('getId')->willReturn(2);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findInactiveUsers')
            ->willReturn([$adminUser, $normalUser]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $this->dispatchedMessages = [];
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($message) {
                $this->dispatchedMessages[] = $message;

                return new Envelope($message);
            });

        $logger = $this->createStub(LoggerInterface::class);

        $handler = new RemoveInactiveUsersHandler($em, $messageBus, $logger);
        $handler($message);

        self::assertCount(1, $this->dispatchedMessages);
        self::assertSame(2, $this->dispatchedMessages[0]->userId);
    }

    public function testSkipsDomainAdminUsers(): void
    {
        $message = new RemoveInactiveUsers();

        $domainAdminUser = $this->createStub(User::class);
        $domainAdminUser->method('hasRole')
            ->willReturnCallback(static fn (string $role) => $role === Roles::DOMAIN_ADMIN);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findInactiveUsers')
            ->willReturn([$domainAdminUser]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('dispatch');

        $logger = $this->createStub(LoggerInterface::class);

        $handler = new RemoveInactiveUsersHandler($em, $messageBus, $logger);
        $handler($message);
    }

    public function testSkipsPermanentUsers(): void
    {
        $message = new RemoveInactiveUsers();

        $permanentUser = $this->createStub(User::class);
        $permanentUser->method('hasRole')
            ->willReturnCallback(static fn (string $role) => $role === Roles::PERMANENT);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findInactiveUsers')
            ->willReturn([$permanentUser]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('dispatch');

        $logger = $this->createStub(LoggerInterface::class);

        $handler = new RemoveInactiveUsersHandler($em, $messageBus, $logger);
        $handler($message);
    }

    public function testHandlesEmptyUserList(): void
    {
        $message = new RemoveInactiveUsers();

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findInactiveUsers')
            ->willReturn([]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->never())->method('dispatch');

        $logger = $this->createStub(LoggerInterface::class);

        $handler = new RemoveInactiveUsersHandler($em, $messageBus, $logger);
        $handler($message);
    }
}
