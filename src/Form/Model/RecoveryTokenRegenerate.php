<?php

declare(strict_types=1);

namespace App\Form\Model;

final class RecoveryTokenRegenerate
{
    private string $password = '';

    private ?string $totpCode = null;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getTotpCode(): ?string
    {
        return $this->totpCode;
    }

    public function setTotpCode(?string $totpCode): void
    {
        $this->totpCode = $totpCode;
    }
}
