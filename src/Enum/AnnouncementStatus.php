<?php

declare(strict_types=1);

namespace App\Enum;

enum AnnouncementStatus: string
{
    /** Announcement is persisted, recipients are not yet queued. */
    case QUEUED = 'queued';

    /** Mails for the recipients are being queued. */
    case SENDING = 'sending';

    /** Mails for all recipients are queued. */
    case DONE = 'done';
}
