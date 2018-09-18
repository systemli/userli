<?php

namespace AppBundle\Traits;

use AppBundle\Entity\Voucher;

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

    /**
     * @param Voucher|null $invitationVoucher
     */
    public function setInvitationVoucher(Voucher $invitationVoucher = null)
    {
        $this->invitationVoucher = $invitationVoucher;
    }
}
