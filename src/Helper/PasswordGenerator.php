<?php

namespace App\Helper;

/**
 * Class PasswordGenerator.
 */
final class PasswordGenerator
{
    /**
     * @throws \Exception
     */
    public static function generate(int $length = 45): string
    {
        $chars = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789,;.:?!';
        $pass = [];
        $charsLength = strlen($chars) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $n = random_int(0, $charsLength);
            $pass[] = $chars[$n];
        }

        return implode($pass);
    }
}
