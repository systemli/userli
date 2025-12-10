<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Voucher>
 */
final class VoucherRepository extends EntityRepository
{
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
        return $this->matching(Criteria::create(true)->where(Criteria::expr()->neq('redeemedTime', null)))->count();
    }

    /**
     * Returns the number of unredeemed vouchers.
     */
    public function countUnredeemedVouchers(): int
    {
        return $this->matching(Criteria::create(true)->where(Criteria::expr()->eq('redeemedTime', null)))->count();
    }

    /**
     * Returns the number of unredeemed vouchers per user, per default
     * Optionally return the number of redeemed vouchers.
     */
    public function countVouchersByUser(User $user, ?bool $redeemed): int
    {
        $criteria = $redeemed ? Criteria::expr()->neq('redeemedTime', null) : Criteria::expr()->eq('redeemedTime', null);

        return $this->matching(Criteria::create(true)
            ->where(Criteria::expr()->eq('user', $user))
            ->andWhere($criteria))
            ->count();
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
