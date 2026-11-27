<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\UserNotificationType;
use App\Mail\UserKeyExpiredMailer;
use App\Message\UserKeyExpiredNotification;
use App\Service\UserNotificationRateLimiter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UserKeyExpiredNotificationHandler
{
    public function __construct(
        private readonly UserKeyExpiredMailer $userKeyExpiredMailer,
        private readonly LoggerInterface $logger,
        private readonly UserNotificationRateLimiter $userNotificationRateLimiter,
    ) {
    }

    public function __invoke(UserKeyExpiredNotification $message): void
    {
        $user = $message->user;
        $key = $message->key;

        $this->userKeyExpiredMailer->sendToUser($user, $key);
        $this->userNotificationRateLimiter->save(
            $user,
            UserNotificationType::OPEN_PGP_KEY_EXPIRED,
            metadata: ['email' => $key->getEmail(), 'fingerprint' => $key->getKeyFingerprint()],
        );
    }
}
