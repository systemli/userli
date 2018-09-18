<?php

namespace AppBundle\Repository;

use AppBundle\Creator\VoucherCreator;
use AppBundle\Entity\User;
use AppBundle\Entity\Voucher;
use AppBundle\Enum\Roles;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * Class VoucherRepository.
 */
class VoucherRepository extends EntityRepository
{
    /**
     * @param $code
     *
     * @return null|Voucher|object
     */
    public function findByCode($code)
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|Voucher[]
     */
    public function findAllRedeemedVouchers()
    {
        return $this->matching(Criteria::create()->where(Criteria::expr()->neq('redeemedTime', null)));
    }

    /**
     * @param User $user
     * @param int  $limit
     *
     * @return Voucher[]|array|null
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findOrCreateByUser(User $user, $limit = 3)
    {
        if ($user->hasRole(Roles::SUSPICIOUS)) {
            return null;
        }

        if ((($vouchersCount = count($vouchers = $this->findByUser($user))) < $limit) && ($user->getCreationTime() < new \DateTime('-7 days'))) {
            for ($i = 0; $i < ($limit - $vouchersCount); ++$i) {
                $voucher = VoucherCreator::create($user);

                $this->_em->persist($voucher);
                $this->_em->flush();

                $vouchers[] = $voucher;
            }
        }

        return $vouchers;
    }

    /**
     * @param User $user
     *
     * @return null|Voucher|object
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createByUser(User $user)
    {
        $voucher = VoucherCreator::create($user);

        $this->_em->persist($voucher);
        $this->_em->flush();

        return $voucher;
    }

    /**
     * @param User $user
     *
     * @return array|Voucher[]
     */
    public function findByUser(User $user)
    {
        return $this->findBy(['user' => $user]);
    }
}
