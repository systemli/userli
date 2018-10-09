<?php

namespace App\Repository;

use App\Entity\ReservedName;

/**
 * Class ReservedNameRepository.
 */
class ReservedNameRepository extends AbstractRepository
{
    /**
     * @param string $name
     *
     * @return null|object|ReservedName
     */
    public function findByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }
}
