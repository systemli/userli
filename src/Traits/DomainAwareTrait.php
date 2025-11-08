<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\Domain;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait DomainAwareTrait
{
    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid]
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
