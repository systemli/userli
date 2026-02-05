<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class RecoveryProcess
{
    #[Assert\Email(mode: 'strict')]
    private string $email;

    #[Assert\Uuid(message: 'form.invalid-token')]
    private string $recoveryToken;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
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
