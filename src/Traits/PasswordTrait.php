<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait PasswordTrait
{
    #[ORM\Column]
    private ?string $password = null;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }
}
