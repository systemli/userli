<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class VoucherUser extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
