<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VoucherRepository;
use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\UserAwareTrait;
use App\Validator\VoucherUser;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Override;
use Stringable;

#[ORM\Entity(repositoryClass: VoucherRepository::class)]
#[ORM\Table(name: 'virtual_vouchers')]
#[Index(name: 'code_idx', columns: ['code'])]
#[VoucherUser]
class Voucher implements Stringable
{
    use CreationTimeTrait;
    use IdTrait;
    use UserAwareTrait;

    #[ORM\Column(nullable: true)]
    protected ?DateTime $redeemedTime = null;

    #[ORM\Column(unique: true)]
    protected ?string $code = null;

    #[ORM\OneToOne(targetEntity: User::class, mappedBy: 'invitationVoucher')]
    protected ?User $invitedUser = null;

    public function __construct()
    {
        $currentDateTime = new DateTime();
        $this->creationTime = $currentDateTime;
    }

    public function getRedeemedTime(): ?DateTime
    {
        return $this->redeemedTime;
    }

    public function setRedeemedTime(?DateTime $redeemedTime): void
    {
        $this->redeemedTime = $redeemedTime;
    }

    public function isRedeemed(): bool
    {
        return null !== $this->getRedeemedTime();
    }

    public function getCode(): string
    {
        return $this->code ?? '';
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
        return (string) $this->code;
    }
}
