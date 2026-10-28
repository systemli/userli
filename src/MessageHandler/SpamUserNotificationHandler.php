<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Enum\Roles;
use App\Mail\SpamUserMailer;
use App\Message\SpamUserNotification;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class SpamUserNotificationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamUserMailer $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SpamUserNotification $message): void
    {
        $user = $this->entityManager->getRepository(User::class)->find($message->userId);

        if (null === $user) {
            $this->logger->warning('User not found for spam notification', ['userId' => $message->userId]);

            return;
        }

        // The role may have been removed again before the message was handled
        if ($user->isDeleted() || !$user->hasRole(Roles::SPAM)) {
            return;
        }

        $this->mailer->send($user);
    }
}
