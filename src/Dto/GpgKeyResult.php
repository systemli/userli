<?php

declare(strict_types=1);

namespace App\Dto;

use DateTimeImmutable;

final readonly class GpgKeyResult
{
    public function __construct(
        public string $email,
        public string $keyId,
        public string $fingerprint,
        public ?DateTimeImmutable $expireTime,
        public string $keyData,
    ) {
    }
}
