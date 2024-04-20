<?php

namespace App\Dto\Traits;

use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

trait PasswordTrait
{
    #[UserPassword]
    private string $password;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
    }
}
