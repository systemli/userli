<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints\TotpSecret;

class TwofactorConfirm
{
    #[Assert\NotNull]
    #[TotpSecret]
    public string $totpSecret;
}
