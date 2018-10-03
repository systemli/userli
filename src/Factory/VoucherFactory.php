<?php

namespace App\Factory;

use App\Entity\User;
use App\Entity\Voucher;
use App\Helper\VoucherCodeGenerator;

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
        $voucher->setCode(VoucherCodeGenerator::generate());

        return $voucher;
    }
}
