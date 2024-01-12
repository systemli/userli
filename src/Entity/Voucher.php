<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\UserAwareTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VoucherRepository")
 * @ORM\Table(name="virtual_vouchers", indexes={
 *     @Index(name="code_idx", columns={"code"})
 * })
 * @ApiResource(
 *     normalizationContext={"enable_max_depth"=true},
 *     security="is_granted('ROLE_ADMIN')",
 *     collectionOperations={
 *         "get",
 *         "post",
 *     },
 *     itemOperations={
 *         "get",
 *         "put",
 *         "delete",
 *     },
 * )
 */
class Voucher
{
    use IdTrait;
    use CreationTimeTrait;
    use UserAwareTrait;

    /** @ORM\Column(nullable=true) */
    protected ?\DateTime $redeemedTime = null;

    /** @ORM\Column(unique=true) */
    protected ?string $code = null;

    protected ?User $invitedUser = null;

    public function __construct()
    {
        $currentDateTime = new \DateTime();
        $this->creationTime = $currentDateTime;
    }

    public function getRedeemedTime(): ?\DateTime
    {
        return $this->redeemedTime;
    }

    public function setRedeemedTime(?\DateTime $redeemedTime): void
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

    public function setInvitedUser(User $invitedUser = null): void
    {
        $this->invitedUser = $invitedUser;
    }

    public function __toString()
    {
        return $this->code;
    }
}
