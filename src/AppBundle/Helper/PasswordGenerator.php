<?php

namespace AppBundle\Helper;

/**
 * Class PasswordGenerator.
 */
final class PasswordGenerator
{
    /**
     * @param int $length
     *
     * @return string
     */
    public static function generate($length = 45)
    {
        $chars = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789,;.:?!';
        $pass = array();
        $charsLength = strlen($chars) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $n = rand(0, $charsLength);
            $pass[] = $chars[$n];
        }

        return implode($pass);
    }
}
