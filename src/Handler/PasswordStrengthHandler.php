<?php

declare(strict_types=1);

namespace App\Handler;

final readonly class PasswordStrengthHandler
{
    private const string REGEX_FORBIDDEN_CHARS = '/[äöüÄÖÜß\'"]/u';

    public function validate(string $value): array
    {
        $errors = [];

        if (preg_match(self::REGEX_FORBIDDEN_CHARS, $value)) {
            $errors[] = 'form.forbidden_char';
        }

        if (strlen($value) < 12) {
            $errors[] = 'form.weak_password';
        }

        return $errors;
    }
}
