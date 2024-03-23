<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait PasswordVersionTrait
{
    #[ORM\Column]
    private ?int $passwordVersion = null;

    public function getPasswordVersion(): ?int
    {
        return $this->passwordVersion;
    }

    public function setPasswordVersion(?int $passwordVersion): void
    {
        $this->passwordVersion = $passwordVersion;
    }
}
