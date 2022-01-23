<?php

namespace App\Traits;

trait QuotaTrait
{
    /**
     * @var int|null
     */
    private $quota;

    public function getQuota(): ?int
    {
        return $this->quota;
    }

    public function setQuota(?int $quota): void
    {
        $this->quota = $quota;
    }
}
