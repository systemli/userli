<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author louis <louis@systemli.org>
 */
class PasswordChangeConstraint extends Constraint
{
    public function validatedBy()
    {
        return 'password_change';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
