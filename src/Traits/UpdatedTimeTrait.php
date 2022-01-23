<?php

namespace App\Traits;

trait UpdatedTimeTrait
{
    /**
     * @var \DateTime|null
     */
    private $updatedTime;

    public function getUpdatedTime(): ?\DateTime
    {
        return $this->updatedTime;
    }

    public function setUpdatedTime(\DateTime $updatedTime): void
    {
        $this->updatedTime = $updatedTime;
    }

    public function updateUpdatedTime(): void
    {
        $this->setUpdatedTime(new \DateTime());
    }
}
