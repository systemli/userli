<?php

declare(strict_types=1);

namespace App\Traits;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait OpenPgpKeyTrait
{
    #[ORM\Column(type: Types::TEXT)]
    public ?string $keyId = null;

    #[ORM\Column(type: Types::TEXT)]
    public ?string $keyFingerprint = null;

    #[ORM\Column(nullable: true)]
    public ?DateTimeImmutable $keyExpireTime = null;

    #[ORM\Column(type: Types::TEXT)]
    public ?string $keyData = null;

    public function getKeyId(): ?string
    {
        return $this->keyId;
    }

    public function setKeyId(?string $keyId): void
    {
        $this->keyId = $keyId;
    }

    public function getKeyFingerprint(): ?string
    {
        return $this->keyFingerprint;
    }

    public function setKeyFingerprint(?string $keyFingerprint): void
    {
        $this->keyFingerprint = $keyFingerprint;
    }

    public function getKeyExpireTime(): ?DateTimeImmutable
    {
        return $this->keyExpireTime;
    }

    public function setKeyExpireTime(?DateTimeImmutable $keyExpireTime): void
    {
        $this->keyExpireTime = $keyExpireTime;
    }

    public function getKeyData(): ?string
    {
        return $this->keyData;
    }

    public function setKeyData(?string $keyData): void
    {
        $this->keyData = $keyData;
    }
}
