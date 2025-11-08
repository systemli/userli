<?php

declare(strict_types=1);

namespace App\Handler;

class PasswordStrengthHandler
{
    private const REGEX_FORBIDDEN_CHARS = '/[äöüÄÖÜß\'"]/u';

    private array $errors = [];

    public function validate($value): array
    {
        if (preg_match(self::REGEX_FORBIDDEN_CHARS, (string) $value)) {
            $this->errors[] = 'form.forbidden_char';
        }

        if (strlen((string) $value) < 12) {
            $this->errors[] = 'form.weak_password';
        }

        return $this->errors;
    }
}
