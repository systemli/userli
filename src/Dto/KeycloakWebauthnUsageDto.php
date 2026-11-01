<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class KeycloakWebauthnUsageDto
{
    #[Assert\PositiveOrZero]
    private int $signCount = 0;

    public function getSignCount(): int
    {
        return $this->signCount;
    }

    public function setSignCount(int $signCount): void
    {
        $this->signCount = $signCount;
    }
}
