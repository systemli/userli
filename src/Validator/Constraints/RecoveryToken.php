<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class RecoveryToken extends Constraint
{
    public $message = 'flashes.recovery-token-invalid';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
