<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Handler\DeleteHandler;
use App\Message\DeleteUser;
use App\MessageHandler\DeleteUserHandler;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeleteUserHandlerTest extends TestCase
{
    public function testDeletesUser(): void
    {
        $userId = 42;
        $message = new DeleteUser($userId);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getEmail')->willReturn('alice@example.org');
        $user->method('isDeleted')->willReturn(false);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(User::class)->willReturn($repository);

        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler->expects($this->once())
            ->method('deleteUser')
            ->with($user);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Deleted user', ['userId' => $userId, 'email' => 'alice@example.org']);

        $handler = new DeleteUserHandler($em, $deleteHandler, $logger);
        $handler($message);
    }

    public function testSkipsNonExistentUser(): void
    {
        $userId = 999;
        $message = new DeleteUser($userId);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(User::class)->willReturn($repository);

        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler->expects($this->never())->method('deleteUser');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('User not found for deletion', ['userId' => $userId]);

        $handler = new DeleteUserHandler($em, $deleteHandler, $logger);
        $handler($message);
    }

    public function testSkipsAlreadyDeletedUser(): void
    {
        $userId = 42;
        $message = new DeleteUser($userId);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('isDeleted')->willReturn(true);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(User::class)->willReturn($repository);

        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler->expects($this->never())->method('deleteUser');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('User already deleted', ['userId' => $userId]);

        $handler = new DeleteUserHandler($em, $deleteHandler, $logger);
        $handler($message);
    }
}
