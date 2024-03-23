<?php

namespace App\Traits;

use App\Entity\Voucher;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait InvitationVoucherTrait.
 */
trait InvitationVoucherTrait
{
    #[ORM\OneToOne(targetEntity: \Voucher::class)]
    private ?Voucher $invitationVoucher = null;

    public function getInvitationVoucher(): ?Voucher
    {
        return $this->invitationVoucher;
    }

    public function setInvitationVoucher(Voucher $invitationVoucher = null): void
    {
        $this->invitationVoucher = $invitationVoucher;
    }
}
