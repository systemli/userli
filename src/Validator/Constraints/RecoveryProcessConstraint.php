<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author doobry <doobry@systemli.org>
 */
class RecoveryProcessConstraint extends Constraint
{
    public function validatedBy()
    {
        return 'recovery_process';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
