<?php

namespace App\Traits;

trait PasswordVersionTrait
{
    /**
     * @var int|null
     */
    private $passwordVersion;

    public function getPasswordVersion(): ?int
    {
        return $this->passwordVersion;
    }

    public function setPasswordVersion(?int $passwordVersion): void
    {
        $this->passwordVersion = $passwordVersion;
    }
}
