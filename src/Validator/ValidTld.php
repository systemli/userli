<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
final class ValidTld extends Constraint
{
    public string $message = 'form.valid-tld';

    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(groups: $groups, payload: $payload);
    }
}
