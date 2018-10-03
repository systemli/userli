<?php

namespace App\Creator;

use App\Entity\User;
use App\Entity\Voucher;
use App\Factory\VoucherFactory;

/**
 * Class VoucherCreator
 */
class VoucherCreator extends AbstractCreator
{
    /**
     * @param User $user
     * @return Voucher
     * @throws \App\Exception\ValidationException
     */
    public function create(User $user): Voucher
    {
        $voucher = VoucherFactory::create($user);

        $this->validate($voucher);
        $this->save($voucher);

        return $voucher;
    }
}
