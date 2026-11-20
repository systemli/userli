<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class UserKeyExpiredNotification
{
    public function __construct(
        public User $user,
        public OpenPgpKey $key,
    ) {
    }
}
