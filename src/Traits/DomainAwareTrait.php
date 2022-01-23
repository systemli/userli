<?php

namespace App\Traits;

use App\Entity\Domain;

trait DomainAwareTrait
{
    /**
     * @var Domain|null
     */
    private $domain;

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(Domain $domain): void
    {
        $this->domain = $domain;
    }
}
