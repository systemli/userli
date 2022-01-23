<?php

namespace App\Repository;

use App\Entity\Alias;
use App\Entity\User;

/**
 * Class AliasRepository.
 */
class AliasRepository extends AbstractRepository
{
    /**
     * @param bool $deleted
     */
    public function findOneBySource(string $email, ?bool $deleted = false): ?Alias
    {
        return $this->findOneBy(['source' => $email], null, $deleted);
    }

    public function findByDestination(string $email): ?Alias
    {
        return $this->findOneBy(['destination' => $email]);
    }

    /**
     * @return array|Alias[]
     */
    public function findByUser(User $user, ?bool $random = null): array
    {
        if (isset($random)) {
            return $this->findBy(['user' => $user, 'random' => $random]);
        }

        return $this->findBy(['user' => $user]);
    }
}
