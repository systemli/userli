<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\RecoveryStatus;
use DateTimeImmutable;

final readonly class RecoveryResult
{
    public function __construct(
        public RecoveryStatus $status,
        public ?DateTimeImmutable $activeTime = null,
        public ?string $recoveryToken = null,
    ) {
    }
}
