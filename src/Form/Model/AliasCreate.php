<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Validator\EmailAddress;
use App\Validator\EmailLength;
use Symfony\Component\Validator\Constraints as Assert;

final class AliasCreate
{
    #[Assert\NotNull]
    #[Assert\Email(mode: 'strict')]
    #[EmailAddress(groups: ['unique'])]
    #[EmailLength(minLength: 3, maxLength: 24)]
    public string $alias;

    #[Assert\Length(max: 40)]
    public ?string $note = null;
}
