<?php

namespace App\Repository;

use App\Entity\Domain;
use Doctrine\ORM\EntityRepository;

/**
 * Class DomainRepository.
 */
class DomainRepository extends EntityRepository
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
