<?php

namespace App\Model;

use DateTime;

class OpenPpgKeyInfo
{
    /** @var string|null */
    private $id;

    /** @var string|null */
    private $fingerprint;

    /** @var DateTime|null */
    private $expireTime;

    /** @var string|null */
    private $data;

    public function __construct(?string $id = null,
                                ?string $fingerprint = null,
                                ?DateTime $expireTime = null,
                                ?string $data = null)
    {
        $this->id = $id;
        $this->fingerprint = $fingerprint;
        $this->expireTime = $expireTime;
        $this->data = $data;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function getExpireTime(): ?DateTime
    {
        return $this->expireTime;
    }

    public function getData(): ?string
    {
        return $this->data;
    }
}
