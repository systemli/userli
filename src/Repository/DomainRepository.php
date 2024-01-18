<?php

namespace App\Repository;

use App\Entity\Domain;
use Doctrine\ORM\EntityRepository;

class DomainRepository extends EntityRepository
{
    public function findByName(string $name): ?Domain
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function getDefaultDomain(): ?Domain
    {
        return $this->findOneBy([], ['id' => 'ASC']);
    }
}
