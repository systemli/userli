<?php

namespace App\Remover;

use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class VoucherRemover.
 */
class VoucherRemover
{
    /**
     * VoucherRemover constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    public function removeUnredeemedVouchersByUser(User $user): void
    {
        $this->removeUnredeemedVouchersByUsers([$user]);
    }

    public function removeUnredeemedVouchersByUsers(array $users): void
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('redeemedTime'))
            ->andWhere(Criteria::expr()->in('user', $users));

        $this->manager->getRepository(Voucher::class)
            ->createQueryBuilder('a')
            ->addCriteria($criteria)
            ->delete()
            ->getQuery()
            ->execute();
    }
}
