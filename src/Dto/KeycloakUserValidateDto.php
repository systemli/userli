<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class KeycloakUserValidateDto {
    #[Assert\NotBlank]
    private string $password = '';

    #[Assert\NotBlank]
    private string $credentialType = 'password';

    public function getPassword(): string {
        return $this->password;
    }

    public function setPassword(string $password): void {
        $this->password = $password;
    }

    public function getCredentialType(): string
    {
        return $this->credentialType;
    }

    public function setCredentialType(string $credentialType): void
    {
        $this->credentialType = $credentialType;
    }
}
