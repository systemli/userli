<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
final class AllowedRoles extends Constraint
{
    public string $message = 'form.role-not-allowed';
}
