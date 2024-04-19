<?php

namespace App\Form\Model;

use App\Traits\PlainPasswordTrait;
use App\Validator\Constraints\EmailAddress;
use App\Validator\Constraints\EmailLength;
use App\Validator\Constraints\VoucherExists;
use Symfony\Component\Validator\Constraints as Assert;

class Registration
{
    use PlainPasswordTrait;

    /**
     * @var string
     */
    #[VoucherExists(exists: true)]
    private $voucher;

    /**
     * @var string
     */
    #[Assert\Email(mode: 'strict', message: 'form.invalid-email')]
    #[EmailAddress]
    #[EmailLength(minLength: 3, maxLength: 32)]
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
