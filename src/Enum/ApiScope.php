<?php

namespace App\Enum;

enum ApiScope: string
{
    case KEYCLOAK = 'keycloak';
    case DOVECOT = 'dovecot';
    case POSTFIX = 'postfix';
    case RETENTION = 'retention';
    case ROUNDCUBE = 'roundcube';

    public static function all(): array
    {
        $scopes = self::cases();

        return array_map(fn(ApiScope $scope) => $scope->value, $scopes);
    }
}
