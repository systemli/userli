<?php

namespace App\Validator\Constraints;

use App\Validator\TotpSecretValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class TotpSecret extends Constraint
{
    public function validatedBy(): string
    {
        return TotpSecretValidator::class;
    }
}
