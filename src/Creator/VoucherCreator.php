<?php

namespace App\Creator;

use App\Entity\User;
use App\Entity\Voucher;
use App\Exception\ValidationException;
use App\Factory\VoucherFactory;

/**
 * Class VoucherCreator.
 */
class VoucherCreator extends AbstractCreator
{
    /**
     * @throws ValidationException
     */
    public function create(User $user): Voucher
    {
        $voucher = VoucherFactory::create($user);

        $this->validate($voucher);
        $this->save($voucher);

        return $voucher;
    }
}
