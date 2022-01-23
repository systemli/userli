<?php

namespace App\Traits;

trait SaltTrait
{
    /**
     * @var string|null
     */
    private $salt;

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(?string $salt): void
    {
        $this->salt = $salt;
    }
}
