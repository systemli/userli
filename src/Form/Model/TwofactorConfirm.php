<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints\TotpSecret;

class TwofactorConfirm
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
