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
     * @param User $user
     *
     * @return OpenPgpKey[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * @param string $email
     *
     * @return OpenPgpKey|null
     */
    public function findByEmail(string $email): ?OpenPgpKey
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function countKeys(): int
    {
        return $this->count([]);
    }
}
