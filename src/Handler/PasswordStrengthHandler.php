<?php

namespace App\Handler;

/**
 * @author louis@systemli.org
 */
class PasswordStrengthHandler
{
    const REGEX_FORBIDDEN_CHARS = '/[äöüÄÖÜß\'"]/';

    /** @var array */
    private $errors = array();

    /**
     * @param $value
     *
     * @return array
     */
    public function validate($value)
    {
        if (preg_match(self::REGEX_FORBIDDEN_CHARS, $value)) {
            $this->errors[] = 'form.forbidden_char';
        }

        if (strlen($value) < 12) {
            $this->errors[] = 'form.weak_password';
        }

        return $this->errors;
    }
}
