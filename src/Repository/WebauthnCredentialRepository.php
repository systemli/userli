<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebauthnCredential>
 */
final class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    /**
     * @return WebauthnCredential[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['creationTime' => 'ASC']);
    }

    public function findOneByCredentialId(string $credentialId): ?WebauthnCredential
    {
        return $this->findOneBy(['credentialIdHash' => WebauthnCredential::hashCredentialId($credentialId)]);
    }

    /**
     * Schedules the removal of all WebAuthn credentials of the user, so it is flushed
     * together with the other changes to the user.
     */
    public function removeByUser(User $user): void
    {
        foreach ($this->findBy(['user' => $user]) as $credential) {
            $this->getEntityManager()->remove($credential);
        }
    }

    public function countUsers(): int
    {
        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(DISTINCT w.user)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
