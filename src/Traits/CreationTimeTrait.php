<?php

namespace App\Traits;

trait CreationTimeTrait
{
    /**
     * @var \DateTime|null
     */
    private $creationTime;

    public function getCreationTime(): ?\DateTime
    {
        return $this->creationTime;
    }

    public function setCreationTime(\DateTime $creationTime): void
    {
        $this->creationTime = $creationTime;
    }

    public function updateCreationTime(): void
    {
        if (null === $this->creationTime) {
            $this->setCreationTime(new \DateTime());
        }
    }
}
