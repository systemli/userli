<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class RecoveryTokenConfirm
{
    private ?string $recoveryToken = null;

    #[Assert\NotBlank(message: 'form.registration-recovery-token-noack')]
    private bool $confirm = false;

    public function getRecoveryToken(): ?string
    {
        return $this->recoveryToken;
    }

    public function setRecoveryToken(?string $recoveryToken): void
    {
        $this->recoveryToken = $recoveryToken;
    }

    public function isConfirm(): bool
    {
        return $this->confirm;
    }

    public function setConfirm(bool $confirm): void
    {
        $this->confirm = $confirm;
    }
}
