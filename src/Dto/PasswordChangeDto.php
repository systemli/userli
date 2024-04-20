<?php

namespace App\Dto;

use App\Dto\Traits\PasswordTrait;
use App\Validator\Constraints\PasswordPolicy;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordChangeDto
{
    use PasswordTrait;

    #[Assert\NotBlank]
    #[Assert\NotCompromisedPassword(skipOnError: 'true')]
    #[Assert\NotIdenticalTo(propertyPath: 'password', message: 'form.identical-passwords')]
    #[PasswordPolicy]
    private string $newPassword;

    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }

    public function setNewPassword(?string $newPassword): void
    {
        $this->newPassword = $newPassword;
    }
}
