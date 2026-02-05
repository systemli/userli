<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Domain;
use App\Validator\Lowercase;
use App\Validator\UniqueField;
use Symfony\Component\Validator\Constraints as Assert;

final class DomainCreate
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Lowercase]
    #[UniqueField(entityClass: Domain::class, field: 'name', message: 'form.unique-field')]
    public string $domain;
}
