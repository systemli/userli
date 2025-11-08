<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class TwofactorBackupConfirm
{
    #[Assert\IsTrue(message: 'form.twofactor-backup-ack-missing')]
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
