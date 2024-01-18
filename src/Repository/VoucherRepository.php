<?php

namespace App\Repository;

use DateTime;
use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

class VoucherRepository extends EntityRepository
{
    /**
     * Finds a voucher by its code.
     * 
     * @param $code
     * @return Voucher|null
     */
    public function findByCode($code): ?Voucher
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Returns the number of redeemed vouchers.
     * 
     * @return int
     */
    public function countRedeemedVouchers(): int
    {
        return $this->matching(Criteria::create()->where(Criteria::expr()->neq('redeemedTime', null)))->count();
    }

    /**
     * Returns the number of unredeemed vouchers.
     * 
     * @return int
     */
    public function countUnredeemedVouchers(): int
    {
        return $this->matching(Criteria::create()->where(Criteria::expr()->eq('redeemedTime', null)))->count();
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
     * Get all redeemed vouchers that are older than 3 months.
     * 
     * @return Voucher[]|array
     */
    public function getOldVouchers(): array
    {
        return $this->createQueryBuilder('voucher')
            ->join('voucher.invitedUser', 'invitedUser')
            ->where('voucher.redeemedTime < :date')
            ->setParameter('date', new DateTime('-3 months'))
            ->orderBy('voucher.redeemedTime')
            ->getQuery()
            ->getResult();
    }
}
