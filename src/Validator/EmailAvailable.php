<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Checks that an e-mail address is not already in use.
 *
 * By default the address is checked against users, aliases and reserved names.
 * Single checks can be disabled, e.g. admins may create several aliases with the
 * same source or aliases for reserved names, but never shadow an existing user.
 */
#[Attribute]
final class EmailAvailable extends Constraint
{
    public function __construct(
        public readonly bool $checkUser = true,
        public readonly bool $checkAlias = true,
        public readonly bool $checkReservedName = true,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(groups: $groups, payload: $payload);
    }
}
