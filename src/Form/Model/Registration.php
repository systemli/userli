<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Traits\PlainPasswordTrait;
use App\Validator\EmailAddress;
use App\Validator\EmailLength;
use App\Validator\VoucherExists;
use Symfony\Component\Validator\Constraints as Assert;

final class Registration
{
    use PlainPasswordTrait;

    #[VoucherExists(exists: true)]
    private string $voucher = '';

    #[Assert\Email(message: 'form.invalid-email', mode: 'strict')]
    #[EmailAddress]
    #[EmailLength(minLength: 3, maxLength: 32)]
    private string $email;

    public function getVoucher(): ?string
    {
        return $this->voucher;
    }

    public function setVoucher(?string $voucher): void
    {
        $this->voucher = $voucher ?? '';
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
