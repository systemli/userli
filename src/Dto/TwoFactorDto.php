<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints\TotpSecret;

class TwoFactorDto
{
    // validated when before being passed to controller -> no getter or setter needed
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[TotpSecret]
    private string $totpSecret;

    public function getTotpSecret()
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(string $totpSecret)
    {
        $this->totpSecret = $totpSecret;
    }
}
