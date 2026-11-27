<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\OpenPgpKey;
use App\Enum\UserNotificationType;
use App\Repository\OpenPgpKeyRepository;
use App\Repository\UserNotificationRepository;
use App\Message\ScanExpiringOpenPgpKeys;
use App\Message\UserKeyExpiredNotification;
use App\Service\OpenPgpKeyManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(sign: true)]
final readonly class ScanExpiringOpenPgpKeysHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private OpenPgpKeyManager $openPgpKeyManager,
        private OpenPgpKeyRepository $openPgpKeyRepository,
        private UserNotificationRepository $userNotificationRepository,
    ) {
    }

    public function __invoke(ScanExpiringOpenPgpKeys $message): void
    {
        $expiringKeys = $this->openPgpKeyRepository->findKeysByExpiry();
        $this->logger->info('Found expiring keys', ['count' => count($expiringKeys)]);
        $dispatched = 0;

        foreach ($expiringKeys as $expiringKey) {
            $email= $expiringKey->getEmail();
            $emailWasNotified = $this->userNotificationRepository->emailHasExpiryNotification($email, UserNotificationType::OPEN_PGP_KEY_EXPIRED);
            if ($emailWasNotified) {
                continue;
            } else {
                $users = $this->openPgpKeyManager->findUsersByEmail($email);
                foreach ($users as $user) {
                    $this->messageBus->dispatch(new UserKeyExpiredNotification($user, $expiringKey));
                    ++$dispatched;
                }
            }
        }
        $this->logger->info('Dispatched user notifications', ['dispatched' => $dispatched]);
    }
}
