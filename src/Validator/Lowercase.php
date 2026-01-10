<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
final class Lowercase extends Constraint
{
    public string $message = 'form.lowercase';

    public string $mode = 'strict';

    public function __construct(?string $mode = null, ?array $groups = null, mixed $payload = null)
    {
        $this->mode = $mode ?? $this->mode;

        parent::__construct(groups: $groups, payload: $payload);
    }
}
