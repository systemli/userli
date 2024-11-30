<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class AdminTouchUserDto
{
    #[Assert\NotBlank]
    private string $email = '';
    private int $timestamp = 0;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
