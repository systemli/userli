<?php

namespace App\Traits;

trait EmailTrait
{
    /**
     * @var string|null
     */
    private $email;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
