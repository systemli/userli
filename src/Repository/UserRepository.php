<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\User;
use DateInterval;
use DateInvalidOperationException;
use DateTime;
use Doctrine\ORM\EntityRepository;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends EntityRepository<User>
 */
final class UserRepository extends EntityRepository implements PasswordUpgraderInterface
{
    public function findById(int $id): ?User
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByDomainAndEmail(Domain $domain, string $email): ?User
    {
        return $this->findOneBy(['domain' => $domain, 'email' => $email]);
    }

    /**
     * @return User[]
     */
    public function findUsersByString(Domain $domain, string $string, int $max, int $first): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.domain = :domain')
            ->andWhere('u.email LIKE :string')
            ->setParameter('domain', $domain)
            ->setParameter('string', '%'.$string.'%')
            ->setMaxResults($max)
            ->setFirstResult($first)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findUsersSince(DateTime $dateTime): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.creationTime >= :dateTime')
            ->setParameter('dateTime', $dateTime)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     *
     * @throws DateInvalidOperationException
     */
    public function findInactiveUsers(int $days): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.deleted = :deleted')
            ->setParameter('deleted', false);

        if ($days > 0) {
            $dateTime = new DateTime();
            $dateTime->sub(new DateInterval('P'.$days.'D'));

            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->lte('u.lastLoginTime', ':dateTime'),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('u.lastLoginTime'),
                        $qb->expr()->lte('u.updatedTime', ':dateTime')
                    )
                )
            )->setParameter('dateTime', $dateTime);
        }

        return $qb->getQuery()->getResult();
    }

    public function findDeletedUsers(?Domain $domain = null): array
    {
        return $domain
            ? $this->findBy(['domain' => $domain, 'deleted' => true])
            : $this->findBy(['deleted' => true]);
    }

    public function countUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deleted = :deleted')
            ->setParameter('deleted', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDomainUsers(Domain $domain): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.domain = :domain')
            ->andWhere('u.deleted = :deleted')
            ->setParameter('domain', $domain)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDeletedUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deleted = :deleted')
            ->setParameter('deleted', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUsersWithRecoveryToken(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deleted = :deleted')
            ->andWhere('u.recoverySecretBox IS NOT NULL')
            ->setParameter('deleted', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUsersWithMailCrypt(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deleted = :deleted')
            ->andWhere('u.mailCryptEnabled = :mailCryptEnabled')
            ->setParameter('deleted', false)
            ->setParameter('mailCryptEnabled', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUsersWithTwofactor(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deleted = :deleted')
            ->andWhere('u.totpConfirmed = :totpConfirmed')
            ->andWhere('u.totpSecret IS NOT NULL')
            ->setParameter('deleted', false)
            ->setParameter('totpConfirmed', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[Override]
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        assert($user instanceof User);
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush($user);
    }
}
