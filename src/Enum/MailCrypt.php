<?php

declare(strict_types=1);

namespace App\Enum;

use InvalidArgumentException;

enum MailCrypt: int
{
    case DISABLED = 0;
    case ENABLED_OPTIONAL = 1;
    case ENABLED_ENFORCE_NEW_USERS = 2;
    case ENABLED_ENFORCE_ALL_USERS = 3;

    public static function fromString(string $value): self
    {
        return match ($value) {
            '0' => self::DISABLED,
            '1' => self::ENABLED_OPTIONAL,
            '2' => self::ENABLED_ENFORCE_NEW_USERS,
            '3' => self::ENABLED_ENFORCE_ALL_USERS,
            default => throw new InvalidArgumentException('Invalid MailCrypt value: '.$value),
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->value >= $other->value;
    }
}
