<?php

declare(strict_types=1);

namespace App\Enum;

enum UserNotificationType: string
{
    case PASSWORD_COMPROMISED = 'password_compromised';
    case OPEN_PGP_KEY_EXPIRED = 'open_pgp_key_expired';
}
