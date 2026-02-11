<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Validator\VoucherExists;
use Symfony\Component\Validator\Constraints as Assert;

final class VoucherCheck
{
    #[Assert\NotBlank]
    #[VoucherExists(exists: true)]
    private string $voucher = '';

    public function getVoucher(): string
    {
        return $this->voucher;
    }

    public function setVoucher(string $voucher): void
    {
        $this->voucher = $voucher;
    }
}
