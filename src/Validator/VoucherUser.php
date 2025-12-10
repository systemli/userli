<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class VoucherUser extends Constraint
{
    #[Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
