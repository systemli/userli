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
    public function findOneByUserAndSource(User $user, string $email, ?bool $random = null): ?Alias
    {
        if (isset($random)) {
            return $this->findOneBy(['user' => $user, 'source' => $email, 'random' => $random, 'deleted' => false]);
        }
        return $this->findOneBy(['user' => $user, 'source' => $email, 'deleted' => false]);
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

        return $this->findOneBy(['source' => $email, 'deleted' => false]);
    }

    /**
     * @param User $user
     * @param bool|null $random
     * @return array|Alias[]
     */
    public function findByUser(User $user, ?bool $random = null, ?bool $disableDomainFilter = false): array
    {
        $filters = $this->getEntityManager()->getFilters();

        if ($filters->isEnabled('domain_filter') && $disableDomainFilter == true) {
            $filters->disable('domain_filter');
        }

        if (isset($random)) {
            return $this->findBy(['user' => $user, 'random' => $random, 'deleted' => false]);
        }

        return $this->findBy(['user' => $user, 'deleted' => false]);
    }

    public function countByUser(User $user, ?bool $random = null): int
    {
        if (isset($random)) {
            return $this->count(['user' => $user, 'random' => $random, 'deleted' => false]);
        }

        return $this->count(['user' => $user, 'deleted' => false]);
    }
}
