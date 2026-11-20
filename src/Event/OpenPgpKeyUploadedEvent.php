<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\UserNotification;
use Symfony\Contracts\EventDispatcher\Event;

final class OpenPgpKeyUploadedEvent extends Event
{
    public const string NAME = 'notification.open_pgp_key_uploaded';

    public function __construct(private readonly string $email)
    {
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
