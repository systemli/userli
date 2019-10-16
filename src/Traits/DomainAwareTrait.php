<?php

namespace App\Traits;

use App\Entity\Domain;

trait DomainAwareTrait
{
    /**
     * @var Domain|null
     */
    private $domain;

    /**
     * @return Domain|null
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param Domain $domain
     */
    public function setDomain(Domain $domain)
    {
        $this->domain = $domain;
    }
}
