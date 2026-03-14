<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
final class EmailDomainAdmin extends Constraint
{
    public string $message = 'form.email-domain-not-allowed';

    public string $domainNotFoundMessage = 'form.email-domain-not-found';
}
