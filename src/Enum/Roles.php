<?php

namespace App\Enum;

final class Roles
{
    const MULTIPLIER = 'ROLE_MULTIPLIER';
    const SPAM = 'ROLE_SPAM';
    const SUSPICIOUS = 'ROLE_SUSPICIOUS';
    const USER = 'ROLE_USER';
    const DOMAIN_ADMIN = 'ROLE_DOMAIN_ADMIN';
    const ADMIN = 'ROLE_ADMIN';

    /**
     * @return array
     */
    public static function getAll()
    {
        return [
            self::MULTIPLIER => self::MULTIPLIER,
            self::SPAM => self::SPAM,
            self::SUSPICIOUS => self::SUSPICIOUS,
            self::USER => self::USER,
            self::DOMAIN_ADMIN => self::DOMAIN_ADMIN,
            self::ADMIN => self::ADMIN,
        ];
    }
}
