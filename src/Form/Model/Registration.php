<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Validator\EmailAddress;
use App\Validator\EmailLength;
use App\Validator\Lowercase;
use App\Validator\PasswordPolicy;
use App\Validator\VoucherExists;
use Symfony\Component\Validator\Constraints as Assert;

final class Registration
{
    #[Assert\NotNull]
    #[VoucherExists(exists: true)]
    private string $voucher = '';

    #[Assert\Email(message: 'form.invalid-email', mode: 'strict')]
    #[EmailAddress]
    #[Assert\NotNull]
    #[Lowercase]
    #[EmailLength(minLength: 3, maxLength: 32)]
    private string $email;

    #[PasswordPolicy]
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
