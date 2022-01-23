<?php

namespace App\Repository;

use App\Entity\ReservedName;

/**
 * Class ReservedNameRepository.
 */
class ReservedNameRepository extends AbstractRepository
{
    public function findByName(string $name): ?ReservedName
    {
        return $this->findOneBy(['name' => $name]);
    }
}
