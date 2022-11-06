<?php

namespace App\Traits;

trait EmailTrait
{
    private string $email = '';

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
