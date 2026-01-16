<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class RecoveryResetPassword
{
    #[Assert\NotNull]
    private string $email;

    #[Assert\NotBlank]
    private string $password;

    #[Assert\NotBlank]
    private string $recoveryToken;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getRecoveryToken(): string
    {
        return $this->recoveryToken;
    }

    public function setRecoveryToken(string $recoveryToken): void
    {
        $this->recoveryToken = $recoveryToken;
    }
}
