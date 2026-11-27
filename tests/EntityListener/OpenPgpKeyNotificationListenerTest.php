<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Entity\UserNotification;
use App\EntityListener\OpenPgpKeyNotificationListener;
use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use App\Service\OpenPgpKeyManager;
use PHPUnit\Framework\TestCase;

class OpenPgpKeyNotificationListenerTest extends TestCase
{
    public function testOnOpenPgpKeyUpdatedDeletesMatchingExpiryNotification(): void
    {
        $user = new User('user@example.org');

        $openPgpKeyManager = $this->createStub(OpenPgpKeyManager::class);
        $openPgpKeyManager->method('findUsersByEmail')
            ->with('key@example.org')
            ->willReturn([$user]);

        $notification = $this->createMock(UserNotification::class);
        $notification->method('getId')->willReturn(42);
        $notification->method('getMetadata')->willReturn(['email' => 'key@example.org']);

        $userNotificationRepository = $this->createMock(UserNotificationRepository::class);
        $userNotificationRepository->method('findNotificationsByUsersAndType')
            ->with([$user], UserNotificationType::OPEN_PGP_KEY_EXPIRED)
            ->willReturn([$notification]);
        $userNotificationRepository->expects(self::once())
            ->method('deleteByIds')
            ->with([42]);

        $listener = new OpenPgpKeyNotificationListener($userNotificationRepository, $openPgpKeyManager);

        $openPgpKey = $this->createStub(OpenPgpKey::class);
        $openPgpKey->method('getEmail')->willReturn('key@example.org');

        $listener->onOpenPgpKeyUpdated($openPgpKey);
    }

    public function testOnOpenPgpKeyUpdatedKeepsNotificationForOtherEmail(): void
    {
        $user = new User('user@example.org');

        $openPgpKeyManager = $this->createStub(OpenPgpKeyManager::class);
        $openPgpKeyManager->method('findUsersByEmail')
            ->willReturn([$user]);

        $notification = $this->createStub(UserNotification::class);
        $notification->method('getId')->willReturn(7);
        $notification->method('getMetadata')->willReturn(['email' => 'other@example.org']);

        $userNotificationRepository = $this->createMock(UserNotificationRepository::class);
        $userNotificationRepository->method('findNotificationsByUsersAndType')
            ->willReturn([$notification]);
        $userNotificationRepository->expects(self::never())
            ->method('deleteByIds');

        $listener = new OpenPgpKeyNotificationListener($userNotificationRepository, $openPgpKeyManager);

        $openPgpKey = $this->createStub(OpenPgpKey::class);
        $openPgpKey->method('getEmail')->willReturn('key@example.org');

        $listener->onOpenPgpKeyUpdated($openPgpKey);
    }

    public function testOnOpenPgpKeyUpdatedWithoutNotificationsDoesNothing(): void
    {
        $user = new User('user@example.org');

        $openPgpKeyManager = $this->createStub(OpenPgpKeyManager::class);
        $openPgpKeyManager->method('findUsersByEmail')
            ->willReturn([$user]);

        $userNotificationRepository = $this->createMock(UserNotificationRepository::class);
        $userNotificationRepository->method('findNotificationsByUsersAndType')
            ->willReturn([]);
        $userNotificationRepository->expects(self::never())
            ->method('deleteByIds');

        $listener = new OpenPgpKeyNotificationListener($userNotificationRepository, $openPgpKeyManager);

        $openPgpKey = $this->createStub(OpenPgpKey::class);
        $openPgpKey->method('getEmail')->willReturn('key@example.org');

        $listener->onOpenPgpKeyUpdated($openPgpKey);
    }
}
