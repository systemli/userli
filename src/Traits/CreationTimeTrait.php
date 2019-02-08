<?php

namespace App\Traits;

/**
 * @author louis <louis@systemli.org>
 */
trait CreationTimeTrait
{
    /**
     * @var \DateTime|null
     */
    private $creationTime;

    /**
     * @return \DateTime|null
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * @param \DateTime $creationTime
     */
    public function setCreationTime(\DateTime $creationTime)
    {
        $this->creationTime = $creationTime;
    }

    public function updateCreationTime()
    {
        if (null === $this->creationTime) {
            $this->setCreationTime(new \DateTime());
        }
    }
}
