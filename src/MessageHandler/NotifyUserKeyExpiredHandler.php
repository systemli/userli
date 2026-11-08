<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Alias;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Message\NotifyUserKeyExpired;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(sign: true)]
final readonly class NotifyUserKeyExpiredHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(NotifyUserKeyExpired $message): void
    {
        $expiringKeys = $this->entityManager->getRepository(OpenPgpKey::class)->findKeysByExpiry(new DateTimeImmutable());
        $this->logger->info('Found expiring keys', ['count' => count($expiringKeys)]);
        $dispatched = 0;

        foreach ($expiringKeys as $expiringKey) {
            $usersNotified = $expiringKey->getExpiryNotifiedAt();
            if ($usersNotified !== null) {
                continue;
            }

            $email = $expiringKey->getEmail();
            // Number of active aliases using this key's email as source address (may be ownerless)
            $aliasCount = $this->entityManager->getRepository(Alias::class)->countBySource($email);

            if ($aliasCount > 0) {
                $recipients = $this->entityManager->getRepository(Alias::class)->findUsersBySource($email);
                if ($recipients === []) {
                    $this->logger->warning('Found expiring key for alias without reachable owner', ['email' => $email]);
                    continue;
                }

                foreach ($recipients as $user) {
                    $this->messageBus->dispatch(new UserNotification($user->getId()));
                    ++$dispatched;
                }
            } else {
                $user = $this->entityManager->getRepository(User::class)->findByEmail($email);
                if ($user === null) {
                    $this->logger->warning('Found expiring key for unknown user', ['email' => $email]);
                    continue;
                }

                $this->messageBus->dispatch(new UserNotification($user->getId()));
                ++$dispatched;
            }
        }

        $this->logger->info('Dispatched user notifications', ['dispatched' => $dispatched]);
    }
}
