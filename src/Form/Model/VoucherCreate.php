<?php

declare(strict_types=1);

namespace App\Form\Model;

final class VoucherCreate
{
    private string $voucher;

    public function getVoucher(): string
    {
        return $this->voucher;
    }

    public function setVoucher(string $voucher): void
    {
        $this->voucher = $voucher;
    }
}
