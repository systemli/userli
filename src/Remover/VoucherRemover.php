<?php

namespace App\Remover;

use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class VoucherRemover.
 */
class VoucherRemover
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * VoucherRemover constructor.
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
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

        $this->manager->getRepository('App:Voucher')
            ->createQueryBuilder('a')
            ->addCriteria($criteria)
            ->delete()
            ->getQuery()
            ->execute();
    }
}
