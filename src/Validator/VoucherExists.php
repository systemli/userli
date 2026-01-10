<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

#[Attribute]
final class VoucherExists extends Constraint
{
    public bool $exists;

    public function __construct(?bool $exists = null, ?array $groups = null, mixed $payload = null)
    {
        if (null === $exists) {
            throw new MissingOptionsException(sprintf('Option "exists" must be given for constraint %s', __CLASS__), ['exists']);
        }

        $this->exists = $exists;

        parent::__construct(groups: $groups, payload: $payload);
    }
}
