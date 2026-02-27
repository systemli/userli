<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\Voucher;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait InvitationVoucherTrait.
 */
trait InvitationVoucherTrait
{
    #[ORM\OneToOne(inversedBy: 'invitedUser', targetEntity: Voucher::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Voucher $invitationVoucher = null;

    public function getInvitationVoucher(): ?Voucher
    {
        return $this->invitationVoucher;
    }

    public function setInvitationVoucher(?Voucher $invitationVoucher = null): void
    {
        $this->invitationVoucher = $invitationVoucher;
    }
}
