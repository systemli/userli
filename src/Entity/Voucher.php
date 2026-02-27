<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VoucherRepository;
use App\Traits\CreationTimeTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\IdTrait;
use App\Traits\UserAwareTrait;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Override;
use Stringable;

/**
 * Invite code for the registration system.
 *
 * Each voucher belongs to a {@see Domain} and can be redeemed exactly once.
 */
#[ORM\Entity(repositoryClass: VoucherRepository::class)]
#[ORM\Table(name: 'vouchers')]
#[Index(columns: ['code'], name: 'code_idx')]
class Voucher implements Stringable
{
    use CreationTimeTrait;
    use DomainAwareTrait;
    use IdTrait;
    use UserAwareTrait;

    /** When the voucher was used to register a new account (null if still available). */
    #[ORM\Column(nullable: true)]
    protected ?DateTimeImmutable $redeemedTime = null;

    /** Unique invite code string shared with prospective users. */
    #[ORM\Column(unique: true)]
    protected string $code;

    /** The user account that was created by redeeming this voucher (null if not yet redeemed). */
    #[ORM\OneToOne(mappedBy: 'invitationVoucher', targetEntity: User::class)]
    protected ?User $invitedUser = null;

    public function __construct(string $code)
    {
        $this->code = $code;
        $currentDateTime = new DateTimeImmutable();
        $this->creationTime = $currentDateTime;
    }

    public function getRedeemedTime(): ?DateTimeImmutable
    {
        return $this->redeemedTime;
    }

    public function setRedeemedTime(?DateTimeImmutable $redeemedTime): void
    {
        $this->redeemedTime = $redeemedTime;
    }

    public function isRedeemed(): bool
    {
        return null !== $this->getRedeemedTime();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getInvitedUser(): ?User
    {
        return $this->invitedUser;
    }

    public function setInvitedUser(?User $invitedUser = null): void
    {
        $this->invitedUser = $invitedUser;
    }

    #[Override]
    public function __toString(): string
    {
        return $this->code;
    }
}
