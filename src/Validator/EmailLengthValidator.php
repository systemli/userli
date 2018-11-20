<?php

namespace App\Validator;

use App\Validator\Constraints\EmailLength;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class EmailLengthValidator.
 *
 * @author doobry <doobry@systemli.org>
 */
class EmailLengthValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param string     $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailLength) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\EmailLength');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $localPart = explode('@', $value)[0];

        if (is_numeric($minLength = $constraint->minLength)) {
            if (strlen($localPart) < $minLength) {
                $this->context->addViolation('registration.email-too-short', array('%min%' => $constraint->minLength));
            }
        }

        if (is_numeric($maxLength = $constraint->maxLength)) {
            if (strlen($localPart) > $maxLength) {
                $this->context->addViolation('registration.email-too-long', array('%max%' => $constraint->maxLength));
            }
        }
    }
}
