<?php

namespace AppBundle\Remover;

use AppBundle\Entity\User;
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
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param User $user
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function removeUnredeemedVouchersByUser(User $user)
    {
        $this->removeUnredeemedVouchersByUsers([$user]);
    }

    /**
     * @param array $users
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function removeUnredeemedVouchersByUsers(array $users)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('redeemedTime'))
            ->andWhere(Criteria::expr()->in('user', $users));

        $this->manager->getRepository('AppBundle:Voucher')
            ->createQueryBuilder('a')
            ->addCriteria($criteria)
            ->delete()
            ->getQuery()
            ->execute();
    }
}
