<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Validator\EmailAllowedSymbols;
use App\Validator\EmailAvailable;
use App\Validator\EmailDomain;
use App\Validator\EmailLength;
use App\Validator\Lowercase;
use App\Validator\VoucherExists;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

final class Registration
{
    #[Assert\NotNull]
    #[VoucherExists(exists: true)]
    private string $voucher = '';

    #[Email(message: 'form.invalid-email', mode: Email::VALIDATION_MODE_STRICT)]
    #[EmailAvailable]
    #[EmailAllowedSymbols]
    #[EmailDomain]
    #[Assert\NotNull]
    #[Lowercase]
    #[EmailLength(minLength: 3, maxLength: 32)]
    private string $email;

    #[Assert\Length(min: 12, minMessage: 'form.weak_password')]
    #[Assert\NotBlank]
    #[Assert\NotCompromisedPassword(skipOnError: true)]
    private string $password;

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

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
