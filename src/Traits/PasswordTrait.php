<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait PasswordTrait
{
    /** @ORM\Column() */
    private ?string $password;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }
}
