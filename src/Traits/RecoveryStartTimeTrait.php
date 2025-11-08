<?php

declare(strict_types=1);

namespace App\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;

trait RecoveryStartTimeTrait
{
    #[ORM\Column(nullable: true)]
    private ?DateTime $recoveryStartTime = null;

    public function getRecoveryStartTime(): ?DateTime
    {
        return $this->recoveryStartTime;
    }

    public function setRecoveryStartTime(DateTime $recoveryStartTime): void
    {
        $this->recoveryStartTime = $recoveryStartTime;
    }

    /**
     * @throws Exception
     */
    public function updateRecoveryStartTime(): void
    {
        $this->setRecoveryStartTime(new DateTime());
    }

    public function eraseRecoveryStartTime(): void
    {
        $this->recoveryStartTime = null;
    }
}
