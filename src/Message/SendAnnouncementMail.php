<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class SendAnnouncementMail
{
    public function __construct(
        public int $announcementId,
        public int $userId,
    ) {
    }
}
