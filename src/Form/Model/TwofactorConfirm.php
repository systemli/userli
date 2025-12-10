<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Validator\TotpSecret;
use Symfony\Component\Validator\Constraints as Assert;

final class TwofactorConfirm
{
    #[Assert\NotNull]
    #[TotpSecret]
    private string $code;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }
}
