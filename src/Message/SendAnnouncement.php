<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * Queues one SendAnnouncementMail per active user of the announcement's domain.
 */
#[AsMessage('async')]
final readonly class SendAnnouncement
{
    public function __construct(
        public int $announcementId,
    ) {
    }
}
