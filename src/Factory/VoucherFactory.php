<?php

namespace App\Factory;

use Exception;
use App\Entity\User;
use App\Entity\Voucher;
use App\Helper\RandomStringGenerator;

/**
 * Interface VoucherFactory.
 */
class VoucherFactory
{
    /**
     * @throws Exception
     */
    public static function create(User $user): Voucher
    {
        $voucher = new Voucher();
        $voucher->setUser($user);
        $voucher->setCode(RandomStringGenerator::generate(6, true));

        return $voucher;
    }
}
