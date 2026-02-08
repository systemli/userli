<?php

declare(strict_types=1);

namespace App\Enum;

enum RecoveryStatus
{
    case Invalid;
    case Started;
    case Pending;
    case Ready;
}
