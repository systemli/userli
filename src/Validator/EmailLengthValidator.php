<?php

declare(strict_types=1);

namespace App\Validator;

use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailLengthValidator extends ConstraintValidator
{
    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailLength) {
            throw new UnexpectedTypeException($constraint, EmailLength::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $localPart = explode('@', $value)[0];

        if (is_numeric($minLength = $constraint->minLength) && strlen($localPart) < $minLength) {
            $this->context->addViolation('registration.email-too-short', ['%min%' => $constraint->minLength]);
        }

        if (is_numeric($maxLength = $constraint->maxLength) && strlen($localPart) > $maxLength) {
            $this->context->addViolation('registration.email-too-long', ['%max%' => $constraint->maxLength]);
        }
    }
}
