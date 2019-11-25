<?php

namespace App\Helper;

/**
 * Class RandomStringGenerator.
 */
class RandomStringGenerator
{
    const LENGTH = 6;

    /**
     * @return string
     */
    public static function generate(int $length = self::LENGTH, bool $caseSensitive = true)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        if (true === $caseSensitive) {
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        $string = '';

        for ($i = 0; $i < $length; ++$i) {
            $string .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $string;
    }
}
