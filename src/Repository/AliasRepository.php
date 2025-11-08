<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Alias;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class AliasRepository extends EntityRepository
{
    public function findOneBySource(string $email, ?bool $includeDeleted = false): ?Alias
    {
        if ($includeDeleted) {
            return $this->findOneBy(['source' => $email]);
        }

        return $this->findOneBy(['source' => $email, 'deleted' => false]);
    }

    /**
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
}
