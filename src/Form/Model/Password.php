<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Validator\PasswordPolicy;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;

final class Password
{
    #[UserPassword(message: 'form.wrong-password')]
    private string $password;

    #[PasswordPolicy]
    #[Assert\NotCompromisedPassword(skipOnError: true)]
    #[Assert\NotIdenticalTo(propertyPath: 'password', message: 'form.identical-passwords')]
    private string $newPassword;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getNewPassword(): string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $newPassword): void
    {
        $this->newPassword = $newPassword;
    }
}
