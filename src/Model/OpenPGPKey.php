<?php

namespace App\Model;

class OpenPGPKey
{
    /** @var string|null */
    private $keyData;

    /** @var string|null */
    private $keyId;

    /** @var string|null */
    private $fingerprint;

    public function __construct(?string $keyData = null,
                                ?string $keyId = null,
                                ?string $fingerprint = null) {
        $this->keyData = $keyData;
        $this->keyId = $keyId;
        $this->fingerprint = $fingerprint;
    }

    public function getData(): ?string
    {
        return $this->keyData;
    }

    public function getId(): ?string
    {
        return $this->keyId;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }
}
