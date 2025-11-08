<?php

declare(strict_types=1);

namespace App\Traits;

trait SaltTrait
{
    private ?string $salt = null;

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(?string $salt): void
    {
        $this->salt = $salt;
    }
}
