<?php

namespace App\Repository;

use App\Entity\OpenPgpKey;
use App\Entity\User;

/**
 * Class OpenPgpKeyRepository.
 */
class OpenPgpKeyRepository extends AbstractRepository
{
    /**
     * @return OpenPgpKey[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function findByEmail(string $email): ?OpenPgpKey
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function countKeys(): int
    {
        return $this->count([]);
    }
}
