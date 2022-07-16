<?php

namespace App\Validator\Constraints;

use App\Validator\TotpSecretValidator;
use Symfony\Component\Validator\Constraint;

class TotpSecret extends Constraint
{
    public function validatedBy(): string
    {
        return TotpSecretValidator::class;
    }
}
