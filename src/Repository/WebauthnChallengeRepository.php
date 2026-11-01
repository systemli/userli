<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnChallenge;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebauthnChallenge>
 */
final class WebauthnChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnChallenge::class);
    }

    /**
     * Removes the challenge and tells whether it was issued for the user and has not expired.
     * A single DELETE, so of concurrent requests with the same challenge only one succeeds.
     */
    public function consume(User $user, string $challenge): bool
    {
        $deleted = $this->createQueryBuilder('c')
            ->delete()
            ->where('c.challengeHash = :hash')
            ->andWhere('c.user = :user')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('hash', WebauthnChallenge::hashChallenge($challenge))
            ->setParameter('user', $user)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->execute();

        return 1 === $deleted;
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.expiresAt <= :now')
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
