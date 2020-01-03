<?php

namespace App\Repository;

use App\Entity\Domain;

/**
 * Class DomainRepository.
 */
class DomainRepository extends AbstractRepository
{
    /**
     * @param $name
     *
     * @return object|Domain|null
     */
    public function findByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function getDefaultDomain(): ?Domain
    {
        return $this->findOneBy([], ['id' => 'ASC']);
    }
}
