<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class OpenPgpKeyRepository extends EntityRepository
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
