<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

use const PHP_INT_MAX;

#[Attribute]
final class EmailLength extends Constraint
{
    public int $minLength;

    public int $maxLength;

    public function __construct(?int $minLength = null, ?int $maxLength = null, ?array $groups = null, mixed $payload = null)
    {
        if (null === $minLength && null === $maxLength) {
            throw new MissingOptionsException(sprintf('Either option "minLength" or "maxLength" must be given for constraint %s', __CLASS__), ['minLength', 'maxLength']);
        }

        $this->minLength = $minLength ?? 0;
        $this->maxLength = $maxLength ?? PHP_INT_MAX;

        parent::__construct(groups: $groups, payload: $payload);
    }
}
