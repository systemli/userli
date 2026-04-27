<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function existsByEmail(string $email): bool
    {
        return (bool) $this->createQueryBuilder('u')
            ->select('1')
            ->where('u.email = :email')
            ->andWhere('u.deleted = :deleted')
            ->setParameter('email', $email)
            ->setParameter('deleted', false)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
    public function findUsersSince(DateTimeImmutable $dateTime): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.creationTime >= :dateTime')
            ->setParameter('dateTime', $dateTime)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findInactiveUsers(int $days): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.deleted = :deleted')
            ->setParameter('deleted', false);

        if ($days > 0) {
            $dateTime = new DateTimeImmutable();
            $dateTime = $dateTime->sub(new DateInterval('P'.$days.'D'));

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

    /**
     * Returns the smtp_quota_limits for a user, or null if the user does not exist.
     *
     * Uses a scalar query to avoid hydrating the full User entity.
     * An existing user with no custom limits returns an empty array.
     *
     * @return array<string, int>|null
     */
    public function findSmtpQuotaLimitsByEmail(string $email): ?array
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.smtpQuotaLimits')
            ->where('u.email = :email')
            ->andWhere('u.deleted = :deleted')
            ->setParameter('email', $email)
            ->setParameter('deleted', false)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result === null) {
            return null;
        }

        return $result['smtpQuotaLimits'] ?? [];
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

    public function countRecentUsers(int $days = 7): int
    {
        $dateTime = new DateTimeImmutable();
        $dateTime = $dateTime->sub(new DateInterval('P'.$days.'D'));

        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deleted = :deleted')
            ->andWhere('u.creationTime >= :dateTime')
            ->setParameter('deleted', false)
            ->setParameter('dateTime', $dateTime)
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

    public function countDomainAdmins(Domain $domain): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.domain = :domain')
            ->andWhere('u.deleted = :deleted')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('domain', $domain)
            ->setParameter('deleted', false)
            ->setParameter('role', '%ROLE_DOMAIN_ADMIN%')
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

    /**
     * @return array{email: string, deleted: bool, mailCryptEnabled: bool, mailCryptPublicKey: ?string, quota: ?int}|null
     */
    public function findLookupDataByEmail(string $email): ?array
    {
        return $this->createQueryBuilder('u')
            ->select('u.email, u.deleted, u.mailCryptEnabled, u.mailCryptPublicKey, u.quota')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countActiveUsersSince(DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deleted = :deleted')
            ->andWhere('u.lastLoginTime >= :since')
            ->setParameter('deleted', false)
            ->setParameter('since', $since)
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

    public function countByFilters(string $search = '', ?Domain $domain = null, string $deleted = 'active', string $role = '', string $mailCrypt = '', string $twofactor = ''): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        $this->applyFilters($qb, $search, $domain, $deleted, $role, $mailCrypt, $twofactor);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return User[]
     */
    public function findPaginatedByFilters(string $search = '', ?Domain $domain = null, string $deleted = 'active', string $role = '', string $mailCrypt = '', string $twofactor = '', int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyFilters($qb, $search, $domain, $deleted, $role, $mailCrypt, $twofactor);

        return $qb->getQuery()->getResult();
    }

    private function applyFilters(QueryBuilder $qb, string $search, ?Domain $domain, string $deleted, string $role, string $mailCrypt, string $twofactor): void
    {
        if ('' !== $search) {
            $qb->andWhere('u.email LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if (null !== $domain) {
            $qb->andWhere('u.domain = :domain')
                ->setParameter('domain', $domain);
        }

        if ('active' === $deleted) {
            $qb->andWhere('u.deleted = :deleted')
                ->setParameter('deleted', false);
        } elseif ('deleted' === $deleted) {
            $qb->andWhere('u.deleted = :deleted')
                ->setParameter('deleted', true);
        }

        if ('' !== $role) {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%'.$role.'%');
        }

        if ('enabled' === $mailCrypt) {
            $qb->andWhere('u.mailCryptEnabled = :mailCryptEnabled')
                ->setParameter('mailCryptEnabled', true);
        } elseif ('disabled' === $mailCrypt) {
            $qb->andWhere('u.mailCryptEnabled = :mailCryptEnabled')
                ->setParameter('mailCryptEnabled', false);
        }

        if ('enabled' === $twofactor) {
            $qb->andWhere('u.totpConfirmed = :totpConfirmed')
                ->andWhere('u.totpSecret IS NOT NULL')
                ->setParameter('totpConfirmed', true);
        } elseif ('disabled' === $twofactor) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'u.totpConfirmed = :totpConfirmed',
                    'u.totpSecret IS NULL'
                )
            )->setParameter('totpConfirmed', false);
        }
    }

    #[Override]
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        assert($user instanceof User);
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }
}
