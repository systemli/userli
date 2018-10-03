<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Factory\VoucherFactory;
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
                $voucher = VoucherFactory::create($user);

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
        $voucher = VoucherFactory::create($user);

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

    /**
     * @return Voucher[]|array
     */
    public function getOldVouchers()
    {
        return $this->createQueryBuilder('voucher')
            ->join('voucher.invitedUser', 'invitedUser')
            ->where('voucher.redeemedTime < :date')
            ->setParameter('date', new \DateTime('-3 months'))
            ->orderBy('voucher.redeemedTime')
            ->getQuery()
            ->getResult();
    }
}
