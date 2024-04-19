<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints\TotpSecret;

class TwofactorConfirm
{
    /** @var string */
    #[Assert\NotNull]
    #[TotpSecret]
    public $totpSecret;
}
