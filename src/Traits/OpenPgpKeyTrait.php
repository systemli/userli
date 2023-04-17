<?php

namespace App\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait OpenPgpKeyTrait
{
    /** @ORM\Column(type="text") */
    public ?string $keyId;

    /** @ORM\Column(type="text") */
    public ?string $keyFingerprint;

    /** @ORM\Column(nullable="true") */
    public ?DateTime $keyExpireTime;

    /** @ORM\Column(type="text") */
    public ?string $keyData;

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

    public function getKeyExpireTime(): ?DateTime
    {
        return $this->keyExpireTime;
    }

    public function setKeyExpireTime(?DateTime $keyExpireTime): void
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
