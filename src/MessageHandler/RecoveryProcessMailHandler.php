<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Handler\RecoveryHandler;
use App\Mail\RecoveryProcessMailer;
use App\Message\RecoveryProcessMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class RecoveryProcessMailHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecoveryProcessMailer $mailer,
    ) {
    }

    public function __invoke(RecoveryProcessMail $message): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $message->email]);

        // The recovery may have been cancelled or finished before the worker picked up the message
        if (null === $user || !RecoveryHandler::isInProgress($user->getRecoveryStartTime())) {
            return;
        }

        $this->mailer->send($user, $message->locale);
    }
}
