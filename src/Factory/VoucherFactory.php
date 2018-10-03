<?php

namespace App\Factory;

use App\Creator\VoucherCodeCreator;
use App\Entity\User;
use App\Entity\Voucher;

/**
 * Interface VoucherFactory
 */
class VoucherFactory
{
    /**
     * @param User $user
     * @return Voucher
     */
    public static function create(User $user): Voucher
    {
        $voucher = new Voucher();
        $voucher->setUser($user);
        $voucher->setCode(VoucherCodeCreator::create());

        return $voucher;
    }
}
