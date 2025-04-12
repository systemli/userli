<?php

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

    /**
     * @return OpenPgpKey[]
     */
    public function findByEmailList(array $emails): array
    {
        $qb = $this->createQueryBuilder('e');

        $qb->where($qb->expr()->in('e.email', ':emails'))
            ->setParameter('emails', $emails);

        return $qb->getQuery()->getResult();
    }
    public function countKeys(): int
    {
        return $this->count([]);
    }
}
