<?php

namespace App\Helper;

use Exception;
/**
 * Class RandomStringGenerator.
 */
class RandomStringGenerator
{
    public const LENGTH = 6;

    /**
     * @throws Exception
     */
    public static function generate(int $length = self::LENGTH, bool $caseSensitive = true): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        if (true === $caseSensitive) {
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        $string = '';

        for ($i = 0; $i < $length; ++$i) {
            $string .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $string;
    }
}
