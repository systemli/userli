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

    /**
     * @return string
     */
    public function getVoucher()
    {
        return $this->voucher;
    }

    public function setVoucher(string $voucher)
    {
        $this->voucher = $voucher;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = strtolower($email);
    }
}
