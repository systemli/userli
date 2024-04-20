<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

#[\Attribute]
class AliasCreate extends Constraint
{
    public int $custom_alias_limit = 3;
    public int $random_alias_limit = 100;

    //#[HasNamedArguments]
    public function __construct(
        int $custom_alias_limit,
        int $random_alias_limit
    ) {
        parent::__construct([]);

        if (null === $custom_alias_limit || null === $random_alias_limit) {
            throw new MissingOptionsException(
                sprintf('Options "custom_alias_limit" and "random_alias_limit" must be given for constraint %s', __CLASS__),
                ['min', 'max']
            );
        }

        $this->custom_alias_limit = $custom_alias_limit ?? $this->custom_alias_limit;
        $this->random_alias_limit = $random_alias_limit ?? $this->random_alias_limit;
    }
}
