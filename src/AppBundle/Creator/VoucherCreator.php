<?php

namespace AppBundle\Creator;

use AppBundle\Entity\User;
use AppBundle\Entity\Voucher;

/**
 * Class VoucherCreator.
 */
class VoucherCreator
{
    /**
     * @param User $user
     *
     * @return Voucher
     */
    public static function create(User $user)
    {
        $voucher = new Voucher();
        $voucher->setUser($user);
        $voucher->setCode(VoucherCodeCreator::create());

        return $voucher;
    }
}
