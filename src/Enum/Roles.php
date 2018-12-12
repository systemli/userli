<?php

namespace App\Enum;

/**
 * @author louis <louis@systemli.org>
 */
final class Roles
{
    const SUSPICIOUS = 'ROLE_SUSPICIOUS';
    const USER = 'ROLE_USER';
    const SUPPORT = 'ROLE_SUPPORT';
    const DOMAIN_ADMIN = 'ROLE_DOMAIN_ADMIN';
    const ADMIN = 'ROLE_ADMIN';

    /**
     * @return array
     */
    public static function getAll()
    {
        return array(
            self::SUSPICIOUS => self::SUSPICIOUS,
            self::USER => self::USER,
            self::SUPPORT => self::SUPPORT,
            self::DOMAIN_ADMIN => self::DOMAIN_ADMIN,
            self::ADMIN => self::ADMIN,
        );
    }
}
