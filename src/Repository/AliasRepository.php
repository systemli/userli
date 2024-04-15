<?php

namespace App\Repository;

use App\Entity\Alias;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class AliasRepository extends EntityRepository
{
    /**
     * @param string    $email
     * @param bool|null $includeDeleted
     * @return Alias|null
     */
    public function findOneBySource(string $email, ?bool $includeDeleted = false): ?Alias
    {
        if ($includeDeleted) {
            return $this->findOneBy(['source' => $email]);
        }

        return $this->findOneBy(['source' => $email, 'deleted' => false]);
    }

    /**
     * @param User $user
     * @param bool|null $random
     * @return array|Alias[]
     */
    public function findByUser(User $user, ?bool $random = null): array
    {
        if (isset($random)) {
            return $this->findBy(['user' => $user, 'random' => $random, 'deleted' => false]);
        }

        return $this->findBy(['user' => $user, 'deleted' => false]);
    }
}
