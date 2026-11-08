<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Mail\UserKeyExpiredMailer;
use App\Message\UserKeyExpiredNotification;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UserKeyExpiredNotificationHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserKeyExpiredMailer $userKeyExpiredMailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handleMessage(UserKeyExpiredNotification $message): void
    {
        $user = $this->entityManager->getRepository(User::class)->find($message->userId);
        if (null === $user) {
            $this->logger->warning('User with id {user-id} not found', ['user-id' => $message->userId]);

            return;
        }

        $key = $this->entityManager->getRepository(OpenPgpKey::class)->find($message->keyId);
        if (null === $key) {
            $this->logger->warning('OpenPgpKey with id {key-id} not found', ['key-id' => $message->keyId]);

            return;
        }

        $this->userKeyExpiredMailer->sendToUser($user, $key);
    }
}
