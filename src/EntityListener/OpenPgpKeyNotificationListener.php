<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\OpenPgpKey;
use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use App\Service\OpenPgpKeyManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preRemove, method: 'onOpenPgpKeyUpdated', entity: OpenPgpKey::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onOpenPgpKeyUpdated', entity: OpenPgpKey::class)]
final readonly class OpenPgpKeyNotificationListener
{
    public function __construct(private UserNotificationRepository $userNotificationRepository,
                                private OpenPgpKeyManager $openPgpKeyManager)
    {
    }

    public function onOpenPgpKeyUpdated(OpenPgpKey $openPgpKey): void
    {
        $email= $openPgpKey->getEmail();
        $users = $this->openPgpKeyManager->findUsersByEmail($email);
        $notifications = $this->userNotificationRepository->findNotificationsByUsersAndType($users, UserNotificationType::OPEN_PGP_KEY_EXPIRED);

        $notificationIdsToDelete = [];
        foreach ($notifications as $notification) {
            $metadata = $notification->getMetadata();
            if ( ($metadata['email'] ?? null) === $email ) {
                $notificationIdsToDelete[] = $notification->getId();
            }
        }

        if ($notificationIdsToDelete !== []) {
            $this->userNotificationRepository->deleteByIds($notificationIdsToDelete);
        }
    }
}