<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VoucherRepository;
use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\UserAwareTrait;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Override;
use Stringable;

#[ORM\Entity(repositoryClass: VoucherRepository::class)]
#[ORM\Table(name: 'vouchers')]
#[Index(columns: ['code'], name: 'code_idx')]
class Voucher implements Stringable
{
    use CreationTimeTrait;
    use IdTrait;
    use UserAwareTrait;

    #[ORM\Column(nullable: true)]
    protected ?DateTimeImmutable $redeemedTime = null;

    #[ORM\Column(unique: true)]
    protected string $code;

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
