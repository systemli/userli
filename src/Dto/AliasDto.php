<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints\AliasCreate;

class AliasDto
{
    #[Assert\NotBlank]
    #[AliasCreate(
        custom_alias_limit: 3,
        random_alias_limit: 100
    )]
    public readonly ?string $localpart;
}
