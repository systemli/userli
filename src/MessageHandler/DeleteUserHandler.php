<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\DeleteUser;
use App\Service\UserLifecycleService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class DeleteUserHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserLifecycleService $userLifecycleService,
        private LoggerInterface $logger,
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

        $this->userLifecycleService->delete($user);
        $this->logger->info('Deleted user', ['userId' => $message->userId, 'email' => $user->getEmail()]);
    }
}
