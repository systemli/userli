<?php

namespace App\Repository;

use App\Entity\Alias;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class AliasRepository extends EntityRepository
{

    /**
     * @return Alias|null
     */
    public function findOneByUserAndSource(User $user, string $email): ?Alias
    {
        return $this->findOneBy([
            'user' => $user,
            'source' => $email,
            'destination' => $user->getEmail(),
            'deleted' => false
        ]);
    }

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

        return $this->findOneBy([
            'source' => $email,
            'deleted' => false
        ]);
    }

    /**
     * @return array|Alias[]
     */
    public function findByUser(User $user, ?bool $random = null): array
    {
        if (isset($random)) {
            return $this->findBy([
                'user' => $user,
                'random' => $random,
                'deleted' => false
            ]);
        }

        return $this->findBy([
            'user' => $user,
            'deleted' => false
        ]);
    }

    public function countByUser(User $user, ?bool $random = null): int
    {
        if (isset($random)) {
            return $this->count([
                'user' => $user,
                'random' => $random,
                'deleted' => false
            ]);
        }

        return $this->count([
            'user' => $user,
            'deleted' => false
        ]);
    }
}
