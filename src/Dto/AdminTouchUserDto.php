<?php

namespace App\Dto;

class AdminTouchUserDto
{
    private int $timestamp = 0;

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
