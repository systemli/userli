<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Mail\RecoveryTokenCreatedMailer;
use App\Message\RecoveryTokenCreatedMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class RecoveryTokenCreatedMailHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecoveryTokenCreatedMailer $mailer,
    ) {
    }

    public function __invoke(RecoveryTokenCreatedMail $message): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $message->email]);
        if (null === $user) {
            return;
        }

        $this->mailer->send($user, $message->locale);
    }
}
