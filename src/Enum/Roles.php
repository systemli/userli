<?php

namespace App\Enum;

/**
 * @author louis <louis@systemli.org>
 */
final class Roles
{
    const ADMIN = 'ROLE_ADMIN';
    const USER = 'ROLE_USER';
    const SUSPICIOUS = 'ROLE_SUSPICIOUS';
    const SUPPORT = 'ROLE_SUPPORT';

    /**
     * @return array
     */
    public static function getAll()
    {
        return array(
            self::ADMIN => self::ADMIN,
            self::USER => self::USER,
            self::SUSPICIOUS => self::SUSPICIOUS,
            self::SUPPORT => self::SUPPORT,
        );
    }
}
