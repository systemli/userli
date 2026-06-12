<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Message\DeleteUser;
use App\MessageHandler\DeleteUserHandler;
use App\Repository\UserRepository;
use App\Service\UserLifecycleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeleteUserHandlerTest extends TestCase
{
    public function testDeletesUser(): void
    {
        $userId = 42;
        $message = new DeleteUser($userId);

        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getEmail')->willReturn('alice@example.org');
        $user->method('isDeleted')->willReturn(false);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $userLifecycleService = $this->createMock(UserLifecycleService::class);
        $userLifecycleService->expects($this->once())
            ->method('delete')
            ->with($user);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Deleted user', ['userId' => $userId, 'email' => 'alice@example.org']);

        $handler = new DeleteUserHandler($em, $userLifecycleService, $logger);
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

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $userLifecycleService = $this->createMock(UserLifecycleService::class);
        $userLifecycleService->expects($this->never())->method('delete');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('User not found for deletion', ['userId' => $userId]);

        $handler = new DeleteUserHandler($em, $userLifecycleService, $logger);
        $handler($message);
    }

    public function testSkipsAlreadyDeletedUser(): void
    {
        $userId = 42;
        $message = new DeleteUser($userId);

        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('isDeleted')->willReturn(true);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $userLifecycleService = $this->createMock(UserLifecycleService::class);
        $userLifecycleService->expects($this->never())->method('delete');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('User already deleted', ['userId' => $userId]);

        $handler = new DeleteUserHandler($em, $userLifecycleService, $logger);
        $handler($message);
    }
}
