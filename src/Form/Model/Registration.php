<?php

namespace App\Form\Model;

use App\Traits\PlainPasswordTrait;

class Registration
{
    use PlainPasswordTrait;

    /**
     * @var string
     */
    private $voucher;

    /**
     * @var string
     */
    private $email;

    public function getVoucher(): ?string
    {
        return $this->voucher;
    }

    public function setVoucher(string $voucher): void
    {
        $this->voucher = $voucher;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = strtolower($email);
    }
}
