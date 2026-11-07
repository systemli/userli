<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\NotifyUserKeyExpired;
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
        $expiringKeys = $this->entityManager->getRepository(OpenPGPKey::class)->findKeysByExpiry(new DateTimeImmutable());
        $this->logger->info('Found expiring keys', ['count' => count($expiringKeys)]);
        $dispatched = 0;

        foreach ($expiringKeys as $expiringKey) {
            $usersNotified = $expiringKey->getExpiryNotifiedAt();
            if ($usersNotified !== null) {
                $email = $this->entityManager->getRepository(OpenPgpKey::class)->getEmail($expiringKey);
                $alias = $this->entityManager->getRepository(Alias::class)->findOneBySource($email);
                if (alias != null){
                    $aliasUsers = array_map(static fn ($alias) => $alias->findUsersForSource(), $aliases);
                    foreach ($aliasUsers as $user) {
                        $this->messageBus->dispatch(new UserNotification($user->getId()));
                        ++$dispatched;
                    }
                }
                else {

                    $user = $this->entityManager->getRepository(UserRepository::Class)->findByEmail($email);
                    $this->messageBus->dispatch(new UserNotification($user->getId()));
                    ++$dispatched;

                }
            }
        }

        $this->logger->info('Dispatched user notifications', ['dispatched' => $dispatched]);
    }
}
