<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ReservedName;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<ReservedName>
 */
class ReservedNameRepository extends EntityRepository
{
    public function findByName(string $name): ?ReservedName
    {
        return $this->findOneBy(['name' => $name]);
    }
}
