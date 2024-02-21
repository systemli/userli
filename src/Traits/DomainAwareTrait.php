<?php

namespace App\Traits;

use App\Entity\Domain;
use Doctrine\ORM\Mapping as ORM;

trait DomainAwareTrait
{
    #[ORM\ManyToOne(targetEntity: \Domain::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Domain $domain = null;

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(Domain $domain): void
    {
        $this->domain = $domain;
    }
}
