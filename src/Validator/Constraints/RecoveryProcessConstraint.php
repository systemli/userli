<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author doobry <doobry@systemli.org>
 */
class RecoveryProcessConstraint extends Constraint
{
    /**
     * @return string
     */
    public function validatedBy(): string
    {
        return 'recovery_process';
    }

    /**
     * @return array|string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
