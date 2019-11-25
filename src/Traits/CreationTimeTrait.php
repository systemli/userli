<?php

namespace App\Traits;

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
