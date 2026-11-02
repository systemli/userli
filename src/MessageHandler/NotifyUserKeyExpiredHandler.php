<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
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
        $expiringKeys = $this->entityManager->getRepository(OpenPGPKey::class)->getExpiringKeys($today);
        $this->logger->info('Found expiring keys', ['count' => count($expiringKeys)]);
        $dispatched = 0;

        foreach ($expiringKeys as $expiringKey) {
            $usersNotified = $expiringKey->getExpiryNotifiedAt();
            if ($usersNotified !== null) {
                $users = $this->entityManager->getRepository(OpenPgpKey::class)->getUsers($expiringKey); // TODO: implement getUsers() on OpenPgyKey
                foreach ($users as $user) {
                    $this->messageBus->dispatch(new UserNotification($user->getId()));
                    $this->eventDispatcher->dispatch(new UserNotificationEvent($user, UserNotificationType::OPENPGP_KEY_EXPIRING, $locale), UserNotificationEvent::OPENPGP_KEY_EXPIRING);
                    ++$dispatched;
                }
            }
            else {
                continue;
            }
        }

        $this->logger->info('Dispatched user notifications', ['dispatched' => $dispatched]);
    }
}
