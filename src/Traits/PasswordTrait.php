<?php

namespace App\Traits;

trait PasswordTrait
{
    /**
     * @var string|null
     */
    private $password;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
