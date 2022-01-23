<?php

namespace App\Repository;

use App\Entity\Domain;

/**
 * Class DomainRepository.
 */
class DomainRepository extends AbstractRepository
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
