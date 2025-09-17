<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class AliasCreate
{
    #[Assert\NotNull]
    #[Assert\Length(min: 3, max: 24)]
    public string $alias;

    #[Assert\Length(max: 40)]
    public ?string $note = null;
}
