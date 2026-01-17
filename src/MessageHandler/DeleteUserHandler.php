<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Handler\DeleteHandler;
use App\Message\DeleteUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteUserHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DeleteHandler $deleteHandler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeleteUser $message): void
    {
        $user = $this->entityManager->getRepository(User::class)->find($message->userId);

        if (null === $user) {
            $this->logger->warning('User not found for deletion', ['userId' => $message->userId]);

            return;
        }

        if ($user->isDeleted()) {
            $this->logger->info('User already deleted', ['userId' => $message->userId]);

            return;
        }

        $this->deleteHandler->deleteUser($user);
        $this->logger->info('Deleted user', ['userId' => $message->userId, 'email' => $user->getEmail()]);
    }
}
