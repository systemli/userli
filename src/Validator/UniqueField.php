<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
final class UniqueField extends Constraint
{
    public string $message = 'form.unique-field';

    /**
     * @param class-string $entityClass
     */
    public function __construct(
        public readonly string $entityClass,
        public readonly string $field,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        if (null !== $message) {
            $this->message = $message;
        }

        parent::__construct(groups: $groups, payload: $payload);
    }
}
