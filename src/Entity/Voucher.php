<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\UserAwareTrait;

/**
 * Class Voucher.
 */
class Voucher
{
    use IdTrait;
    use CreationTimeTrait;
    use UserAwareTrait;
    /**
     * @var \DateTime
     */
    protected $redeemedTime;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var User|null
     */
    protected $invitedUser;

    /**
     * @var \DateTime
     */
    protected $updatedTime;

    public function __construct()
    {
        $currentDateTime = new \DateTime();
        $this->creationTime = $currentDateTime;
        $this->updatedTime = $currentDateTime;
    }

    public function getRedeemedTime(): ?\DateTime
    {
        return $this->redeemedTime;
    }

    public function setRedeemedTime(\DateTime $redeemedTime): void
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
}
