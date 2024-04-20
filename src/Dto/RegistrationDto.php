<?php

namespace App\Dto;

use App\Validator\Constraints\PasswordPolicy;
use App\Validator\Constraints\VoucherExists;
use App\Validator\Constraints\EmailAddress;
use App\Validator\Constraints\EmailLength;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationDto
{
    #[Assert\Email]
    #[EmailAddress]
    #[EmailLength(minLength: 3, maxLength: 32)]
    public string $email;

    #[Assert\NotCompromisedPassword(skipOnError: 'true')]
    #[PasswordPolicy]
    private string $newPassword;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[VoucherExists(exists: 'false')]
    public string $voucher;

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $newPassword)
    {
        $this->newPassword = $newPassword;
    }
}
