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

    /**
     * @return Voucher|null
     */
    public function getInvitationVoucher()
    {
        return $this->invitationVoucher;
    }

    public function setInvitationVoucher(Voucher $invitationVoucher = null)
    {
        $this->invitationVoucher = $invitationVoucher;
    }
}
