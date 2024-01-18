<?php

namespace App\Repository;

use App\Entity\Alias;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class AliasRepository extends EntityRepository
{
    /**
     * @param string $email
     * @return Alias|null
     */
    public function findOneBySource(string $email): ?Alias
    {
        return $this->findOneBy(['source' => $email]);
    }

    /**
     * @param User $user
     * @param bool|null $random
     * @param bool $deleted
     * @return array|Alias[]
     */
    public function findByUser(User $user, ?bool $random = null, ?bool $deleted = false): array
    {
        if (isset($random)) {
            return $this->findBy(['user' => $user, 'random' => $random, 'deleted' => $deleted]);
        }

        return $this->findBy(['user' => $user, 'deleted' => $deleted]);
    }
}
