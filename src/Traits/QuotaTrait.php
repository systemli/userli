<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait QuotaTrait
{
    /** @ORM\Column(nullable=true) */
    private ?int $quota = null;

    public function getQuota(): ?int
    {
        return $this->quota;
    }

    public function setQuota(?int $quota): void
    {
        $this->quota = $quota;
    }
}
