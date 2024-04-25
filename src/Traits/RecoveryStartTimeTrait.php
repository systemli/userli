<?php

namespace App\Traits;

use DateTimeImmutable;
use Exception;
use Doctrine\ORM\Mapping as ORM;

trait RecoveryStartTimeTrait
{
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $recoveryStartTime = null;

    public function getRecoveryStartTime(): ?DateTimeImmutable
    {
        return $this->recoveryStartTime;
    }

    public function setRecoveryStartTime(DateTimeImmutable $recoveryStartTime): void
    {
        $this->recoveryStartTime = $recoveryStartTime;
    }

    /**
     * @throws Exception
     */
    public function updateRecoveryStartTime(): void
    {
        $this->setRecoveryStartTime(new DateTimeImmutable());
    }

    public function eraseRecoveryStartTime(): void
    {
        $this->recoveryStartTime = null;
    }
}
