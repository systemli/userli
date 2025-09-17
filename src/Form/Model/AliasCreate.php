<?php

namespace App\Form\Model;

use App\Validator\EmailAddress;
use App\Validator\EmailLength;
use Symfony\Component\Validator\Constraints as Assert;

class AliasCreate
{
    #[Assert\NotNull]
    #[Assert\Email(mode: 'strict')]
    #[EmailAddress(groups: ['unique'])]
    #[EmailLength(minLength: 3, maxLength: 24)]
    public string $alias;

    #[Assert\Length(max: 50)]
    public ?string $note = null;
}
