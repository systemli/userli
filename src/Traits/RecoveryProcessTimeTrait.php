<?php

namespace App\Traits;

/**
 * @author doobry <doobry@systemli.org>
 */
trait RecoveryProcessTimeTrait
{
    /**
     * @var null|\DateTime
     */
    private $recoveryProcessTime;

    /**
     * @return \DateTime|null
     */
    public function getRecoveryProcessTime()
    {
        return $this->recoveryProcessTime;
    }

    /**
     * @param \DateTime $recoveryProcessTime
     */
    public function setRecoveryProcessTime(\DateTime $recoveryProcessTime)
    {
        $this->recoveryProcessTime = $recoveryProcessTime;
    }

    public function updateRecoveryProcessTime()
    {
        $this->setRecoveryProcessTime(new \DateTime());
    }
}
