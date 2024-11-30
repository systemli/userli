<?php

namespace App\Dto;

class RetentionTouchUserDto
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
