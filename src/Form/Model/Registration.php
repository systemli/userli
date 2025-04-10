<?php

namespace App\Form\Model;

use App\Validator\Constraints\VoucherExists;

class Registration extends BasicRegistration
{
    #[VoucherExists(exists: true)]
    private string $voucher = '';

    public function getVoucher(): ?string
    {
        return $this->voucher;
    }

    public function setVoucher(string $voucher): void
    {
        $this->voucher = $voucher;
    }
}
