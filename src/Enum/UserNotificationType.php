<?php

declare(strict_types=1);

namespace App\Enum;

enum UserNotificationType: string
{
    case PASSWORD_COMPROMISED = 'password_compromised';
    case OPENPGP_KEY_EXPIRING = 'openpgp_key_expiring';
}
