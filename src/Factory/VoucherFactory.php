<?php

namespace App\Factory;

use App\Entity\User;
use App\Entity\Voucher;
use App\Helper\RandomStringGenerator;

/**
 * Interface VoucherFactory.
 */
class VoucherFactory
{
    /**
     * @param User $user
     *
     * @return Voucher
     */
    public static function create(User $user): Voucher
    {
        $voucher = new Voucher();
        $voucher->setUser($user);
        $voucher->setCode(RandomStringGenerator::generate(6, true));

        return $voucher;
    }
}
