<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\WelcomeMail;
use App\Sender\WelcomeMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class WelcomeMailHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WelcomeMessageSender $sender,
    ) {
    }

    public function __invoke(WelcomeMail $message): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $message->email]);
        if (null === $user) {
            return;
        }

        $this->sender->send($user, $message->locale);
    }
}
