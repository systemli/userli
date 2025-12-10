<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class RecoveryProcess
{
    #[Assert\Email(mode: 'strict')]
    public string $email;

    #[Assert\Uuid(message: 'form.invalid-token')]
    public string $recoveryToken;
}
