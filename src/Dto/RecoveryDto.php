<?php

namespace App\Dto;

use App\Validator\Constraints\RecoveryToken;
use App\Validator\Constraints\PasswordPolicy;
use Symfony\Component\Validator\Constraints as Assert;

#[RecoveryToken]
class RecoveryDto
{
    #[Assert\NotBlank]
    #[Assert\Email(mode: 'strict')]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\NotCompromisedPassword(skipOnError: 'true')]
    #[PasswordPolicy]
    private string $newPassword;

    #[Assert\NotBlank]
    private string $recoveryToken;

    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }

    public function setNewPassword(?string $newPassword): void
    {
        $this->newPassword = $newPassword;
    }

    public function lowerCaseRecoveryToken(): string
    {
        return strtolower($this->recoveryToken);
    }

    public function setRecoveryToken(string $recoveryToken): void
    {
        $this->recoveryToken = $recoveryToken;
    }
}
