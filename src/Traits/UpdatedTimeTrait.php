<?php

namespace App\Traits;

trait UpdatedTimeTrait
{
    /**
     * @var \DateTime|null
     */
    private $updatedTime;

    /**
     * @return \DateTime|null
     */
    public function getUpdatedTime()
    {
        return $this->updatedTime;
    }

    public function setUpdatedTime(\DateTime $updatedTime)
    {
        $this->updatedTime = $updatedTime;
    }

    public function updateUpdatedTime()
    {
        $this->setUpdatedTime(new \DateTime());
    }
}
