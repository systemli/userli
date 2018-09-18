<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author louis <louis@systemli.org>
 */
class PasswordPolicy extends Constraint
{
    public function validatedBy()
    {
        return 'password_policy';
    }
}
