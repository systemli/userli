<?php

namespace App\Traits;

/**
 * @author doobry <doobry@systemli.org>
 */
trait RecoveryStartTimeTrait
{
    /**
     * @var null|\DateTime
     */
    private $recoveryStartTime;

    /**
     * @return \DateTime|null
     */
    public function getRecoveryStartTime(): ?\DateTime
    {
        return $this->recoveryStartTime;
    }

    /**
     * @param \DateTime $recoveryStartTime
     */
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
}
