<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Mail\WelcomeMailer;
use App\Message\WelcomeMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class WelcomeMailHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WelcomeMailer $mailer,
    ) {
    }

    public function __invoke(WelcomeMail $message): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $message->email]);
        if (null === $user) {
            return;
        }

        $this->mailer->send($user, $message->locale);
    }
}
