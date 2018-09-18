<?php

namespace AppBundle\Traits;

/**
 * @author louis <louis@systemli.org>
 */
trait UpdatedTimeTrait
{
    /**
     * @var null|\DateTime
     */
    private $updatedTime;

    /**
     * @return \DateTime|null
     */
    public function getUpdatedTime()
    {
        return $this->updatedTime;
    }

    /**
     * @param \DateTime $updatedTime
     */
    public function setUpdatedTime(\DateTime $updatedTime)
    {
        $this->updatedTime = $updatedTime;
    }

    public function updateUpdatedTime()
    {
        $this->setUpdatedTime(new \DateTime());
    }
}
