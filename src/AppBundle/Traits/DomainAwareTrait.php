<?php

namespace AppBundle\Traits;

use AppBundle\Entity\Domain;

/**
 * @author louis <louis@systemli.org>
 */
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
