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

    public function setRecoveryStartTime(\DateTime $recoveryStartTime): void
    {
        $this->recoveryStartTime = $recoveryStartTime;
    }

    /**
     * @throws \Exception
     */
    public function updateRecoveryStartTime(): void
    {
        $this->setRecoveryStartTime(new \DateTime());
    }

    public function eraseRecoveryStartTime(): void
    {
        $this->recoveryStartTime = null;
    }
}
