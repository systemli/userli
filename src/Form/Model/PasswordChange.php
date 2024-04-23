<?php

namespace App\Form\Model;

use App\Validator\Constraints\PasswordPolicy;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class PasswordChange
{
    #[UserPassword(message: 'form.wrong-password')]
    private string $password;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    #[PasswordPolicy]
    #[Assert\NotCompromisedPassword(skipOnError: 'true')]
    #[Assert\NotIdenticalTo(propertyPath: 'password', message: 'form.identical-passwords')]
    private string $plainPassword;

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }
}
