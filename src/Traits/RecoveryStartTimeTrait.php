<?php

namespace App\Traits;

trait RecoveryStartTimeTrait
{
    /**
     * @var \DateTime|null
     */
    private $recoveryStartTime;

    public function getRecoveryStartTime(): ?\DateTime
    {
        return $this->recoveryStartTime;
    }

    public function setRecoveryStartTime(\DateTime $recoveryStartTime)
    {
        $this->recoveryStartTime = $recoveryStartTime;
    }

    /**
     * @throws \Exception
     */
    public function updateRecoveryStartTime()
    {
        $this->setRecoveryStartTime(new \DateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function eraseRecoveryStartTime()
    {
        $this->recoveryStartTime = null;
    }
}
