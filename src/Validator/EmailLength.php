<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

#[Attribute]
class EmailLength extends Constraint
{
    public int $minLength;

    public int $maxLength;

    /**
     * {@inheritdoc}
     */
    public function __construct(?int $minLength = null, ?int $maxLength = null)
    {
        parent::__construct([]);

        if (null === $minLength && null === $maxLength) {
            throw new MissingOptionsException(sprintf('Either option "minLength" or "maxLength" must be given for constraint %s', __CLASS__), ['min', 'max']);
        }

        $this->minLength = $minLength ?? $this->minLength;
        $this->maxLength = $maxLength ?? $this->maxLength;
    }
}
