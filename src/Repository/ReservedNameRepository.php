<?php

namespace App\Repository;

use App\Entity\ReservedName;
use Doctrine\ORM\EntityRepository;

class ReservedNameRepository extends EntityRepository
{
    public function findByName(string $name): ?ReservedName
    {
        return $this->findOneBy(['name' => $name]);
    }
}
