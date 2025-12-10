<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use App\Entity\Voucher;
use App\Helper\RandomStringGenerator;
use Exception;

/**
 * Interface VoucherFactory.
 */
final class VoucherFactory
{
    /**
     * @throws Exception
     */
    public static function create(User $user): Voucher
    {
        $voucher = new Voucher(RandomStringGenerator::generate(6, true));
        $voucher->setUser($user);

        return $voucher;
    }
}
