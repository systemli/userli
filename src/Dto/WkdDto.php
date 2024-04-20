<?php

namespace App\Dto;

use App\Dto\Traits\PasswordTrait;
use Symfony\Component\Validator\Constraints as Assert;

class WkdDto
{
    use PasswordTrait;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    public string $keydata;
}
