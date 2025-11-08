<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Traits\RecoveryTokenTrait;
use Symfony\Component\Validator\Constraints as Assert;

class RecoveryTokenConfirm
{
    use RecoveryTokenTrait;

    #[Assert\NotBlank(message: 'form.registration-recovery-token-noack')]
    private bool $confirm = false;

    public function isConfirm(): bool
    {
        return $this->confirm;
    }

    public function setConfirm(bool $confirm): void
    {
        $this->confirm = $confirm;
    }
}
