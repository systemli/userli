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
     * @return null|object|Domain
     */
    public function findByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }
}
