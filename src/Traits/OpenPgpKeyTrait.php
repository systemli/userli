<?php

namespace App\Traits;

use DateTime;

trait OpenPgpKeyTrait
{
    /** @var string */
    public $keyId;

    /** @var string */
    public $keyFingerprint;

    /** @var DateTime|null */
    public $keyExpireTime;

    /** @var string */
    public $keyData;

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
