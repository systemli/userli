<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
final class EmailAddress extends Constraint
{
    public function __construct(public bool $exists = true, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(groups: $groups, payload: $payload);
    }
}
