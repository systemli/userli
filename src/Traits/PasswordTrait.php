<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stores the hashed password (persisted). The hashing algorithm is managed by Symfony's password hasher.
 */
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
