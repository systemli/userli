<?php

namespace App\Helper;

/**
 * Class VoucherCodeGenerator
 */
class VoucherCodeGenerator
{
    const LENGTH = 6;

    public static function generate($length = self::LENGTH)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';

        for ($i = 0; $i < $length; ++$i) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $code;
    }
}
