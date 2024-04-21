<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class KeycloakUserValidateDto {
    #[Assert\NotBlank]
    public string $domain;
    #[Assert\NotBlank]
    public string $password = '';
}
