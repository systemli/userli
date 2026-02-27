<?php

declare(strict_types=1);

namespace App\Handler;

/**
 * Validates password strength: rejects German special characters (umlauts, eszett, quotes) and passwords under 12 chars.
 */
final class PasswordStrengthHandler
{
    private const string REGEX_FORBIDDEN_CHARS = '/[äöüÄÖÜß\'"]/u';

    private array $errors = [];

    public function validate(string $value): array
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
