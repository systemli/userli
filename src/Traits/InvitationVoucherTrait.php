<?php

namespace App\Traits;

use App\Entity\Voucher;

/**
 * Trait InvitationVoucherTrait.
 */
trait InvitationVoucherTrait
{
    /**
     * @var Voucher|null
     */
    private $invitationVoucher;

    public function getInvitationVoucher(): ?Voucher
    {
        return $this->invitationVoucher;
    }

    public function setInvitationVoucher(Voucher $invitationVoucher = null): void
    {
        $this->invitationVoucher = $invitationVoucher;
    }
}
