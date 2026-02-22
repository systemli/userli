<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /**
     * Finds a voucher by its code.
     */
    public function findByCode(string $code): ?Voucher
    {
        return $this->findOneBy(['code' => $code]);
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
