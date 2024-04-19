<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

#[\Attribute]
class VoucherExists extends Constraint
{
    public bool $exists;
    /**
     * {@inheritdoc}
     */
    public function __construct(?bool $exists = null)
    {
        parent::__construct([]);

        if (null === $exists) {
            throw new MissingOptionsException(
                sprintf('Option "exists" must be given for constraint %s', __CLASS__), ['min', 'max']
            );
        }
        $this->exists = $exists ?? $this->exists;
    }
}
