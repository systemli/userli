<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class KeycloakUserValidateDto {
    #[Assert\NotBlank]
    private string $domain;
    #[Assert\NotBlank]
    private string $password = '';

    public function getDomain(): string {
        return $this->domain;
    }

    public function setDomain(string $domain): void {
        $this->domain = $domain;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function setPassword(string $password): void {
        $this->password = $password;
    }
}
