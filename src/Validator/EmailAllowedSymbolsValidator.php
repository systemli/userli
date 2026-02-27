<?php

declare(strict_types=1);

namespace App\Validator;

use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailAllowedSymbolsValidator extends ConstraintValidator
{
    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailAllowedSymbols) {
            throw new UnexpectedTypeException($constraint, EmailAllowedSymbols::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        [$localPart] = explode('@', $value);

        if (1 !== preg_match('/^[a-z0-9\-_.]*$/ui', $localPart)) {
            $this->context->addViolation('registration.email-unexpected-characters');
        }
    }
}
