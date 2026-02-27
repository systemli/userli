<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tracks the password hashing version to support transparent migration between hashing algorithms.
 */
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
