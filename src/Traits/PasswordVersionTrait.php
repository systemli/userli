<?php

namespace App\Traits;

trait PasswordVersionTrait
{
    /**
     * @var int|null
     */
    private $passwordVersion;

    /**
     * @return int|null
     */
    public function getPasswordVersion(): ?int
    {
        return $this->passwordVersion;
    }

    /**
     * @param int|null $passwordVersion
     */
    public function setPasswordVersion(?int $passwordVersion): void
    {
        $this->passwordVersion = $passwordVersion;
    }
}
