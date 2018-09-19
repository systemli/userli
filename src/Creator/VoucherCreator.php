<?php

namespace App\Creator;

use App\Entity\User;
use App\Entity\Voucher;

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
