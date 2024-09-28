<?php

namespace App\Form\Model;

use App\Traits\PlainPasswordTrait;
use App\Validator\Constraints\EmailAddress;
use App\Validator\Constraints\EmailLength;
use Symfony\Component\Validator\Constraints as Assert;

class BasicRegistration
{
    use PlainPasswordTrait;

    #[Assert\Email(message: 'form.invalid-email', mode: 'strict')]
    #[EmailAddress]
    #[EmailLength(minLength: 3, maxLength: 32)]
    private string $email;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = strtolower($email);
    }
}
