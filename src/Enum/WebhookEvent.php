<?php

declare(strict_types=1);

namespace App\Enum;

enum WebhookEvent: string
{
    case USER_CREATED = 'user.created';
    case USER_DELETED = 'user.deleted';
    case USER_RESET = 'user.reset';

    public static function all(): array
    {
        $scopes = self::cases();

        return array_map(static fn (WebhookEvent $scope) => $scope->value, $scopes);
    }
}
