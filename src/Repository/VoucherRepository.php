<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voucher>
 */
final class VoucherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voucher::class);
    }

    public function countByFilters(string $search = '', ?Domain $domain = null, string $status = ''): int
    {
        $qb = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)');

        $this->applyFilters($qb, $search, $domain, $status);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Voucher[]
     */
    public function findPaginatedByFilters(string $search = '', ?Domain $domain = null, string $status = '', int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('v')
            ->orderBy('v.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $this->applyFilters($qb, $search, $domain, $status);

        return $qb->getQuery()->getResult();
    }

    private function applyFilters(QueryBuilder $qb, string $search, ?Domain $domain, string $status): void
    {
        if ('' !== $search) {
            $qb->leftJoin('v.user', 'u')
                ->andWhere('v.code LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if (null !== $domain) {
            $qb->andWhere('v.domain = :domain')
                ->setParameter('domain', $domain);
        }

        if ('redeemed' === $status) {
            $qb->andWhere('v.redeemedTime IS NOT NULL');
        } elseif ('unredeemed' === $status) {
            $qb->andWhere('v.redeemedTime IS NULL');
        }
    }

    /**
     * Finds a voucher by its code.
     */
    public function findByCode(string $code): ?Voucher
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Returns the number of vouchers for a domain.
     */
    public function countByDomain(Domain $domain): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.domain = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the number of redeemed vouchers.
     */
    public function countRedeemedVouchers(): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.redeemedTime IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the number of unredeemed vouchers.
     */
    public function countUnredeemedVouchers(): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.redeemedTime IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the number of unredeemed vouchers per user, per default
     * Optionally return the number of redeemed vouchers.
     */
    public function countVouchersByUser(User $user, ?bool $redeemed): int
    {
        $qb = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.user = :user')
            ->setParameter('user', $user);

        if ($redeemed) {
            $qb->andWhere('v.redeemedTime IS NOT NULL');
        } else {
            $qb->andWhere('v.redeemedTime IS NULL');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Finds all vouchers for a given user.
     *
     * @return array|Voucher[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * Get all redeemed vouchers for a user.
     *
     * @return Voucher[]|array
     */
    public function getRedeemedVouchersByUser(User $user): array
    {
        return $this->createQueryBuilder('voucher')
            ->join('voucher.invitedUser', 'invitedUser')
            ->where('voucher.user = :user')
            ->setParameter('user', $user)
            ->andWhere('voucher.redeemedTime IS NOT NULL')
            ->orderBy('voucher.redeemedTime')
            ->getQuery()
            ->getResult();
    }
}
