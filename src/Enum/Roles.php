<?php

namespace App\Enum;

final class Roles
{
    public const PERMANENT = 'ROLE_PERMANENT';
    public const MULTIPLIER = 'ROLE_MULTIPLIER';
    public const SPAM = 'ROLE_SPAM';
    public const SUSPICIOUS = 'ROLE_SUSPICIOUS';
    public const USER = 'ROLE_USER';
    public const DOMAIN_ADMIN = 'ROLE_DOMAIN_ADMIN';
    public const ADMIN = 'ROLE_ADMIN';
    public const API_KEYCLOAK = 'ROLE_API_KEYCLOAK';

    public static function getAll(): array
    {
        return [
            self::PERMANENT => self::PERMANENT,
            self::MULTIPLIER => self::MULTIPLIER,
            self::SPAM => self::SPAM,
            self::SUSPICIOUS => self::SUSPICIOUS,
            self::USER => self::USER,
            self::DOMAIN_ADMIN => self::DOMAIN_ADMIN,
            self::ADMIN => self::ADMIN,
            self::API_KEYCLOAK => self::API_KEYCLOAK,
        ];
    }
}
