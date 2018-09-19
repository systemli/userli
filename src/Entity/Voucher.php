<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\IdTrait;
use App\Traits\UserAwareTrait;

/**
 * @author louis <louis@systemli.org>
 */
class Voucher
{
    use IdTrait, CreationTimeTrait, UserAwareTrait;
    /**
     * @var \DateTime
     */
    protected $redeemedTime = null;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var User|null
     */
    protected $invitedUser = null;

    /**
     * {@inheritdoc}
     */
    public function getRedeemedTime()
    {
        return $this->redeemedTime;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedeemedTime(\DateTime $redeemedTime)
    {
        $this->redeemedTime = $redeemedTime;
    }

    /**
     * {@inheritdoc}
     */
    public function isRedeemed()
    {
        return null !== $this->getRedeemedTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * {@inheritdoc}
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return null|User
     */
    public function getInvitedUser()
    {
        return $this->invitedUser;
    }

    /**
     * @param User $invitedUser
     */
    public function setInvitedUser(User $invitedUser = null)
    {
        $this->invitedUser = $invitedUser;
    }
}
