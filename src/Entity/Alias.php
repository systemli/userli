<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\DeleteTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\IdTrait;
use App\Traits\RandomTrait;
use App\Traits\UpdatedTimeTrait;
use App\Traits\UserAwareTrait;

class Alias implements SoftDeletableInterface
{
    use IdTrait;
    use CreationTimeTrait;
    use UpdatedTimeTrait;
    use DeleteTrait;
    use DomainAwareTrait;
    use UserAwareTrait;
    use RandomTrait;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $destination;

    /**
     * Alias constructor.
     */
    public function __construct()
    {
        $this->deleted = false;
        $this->random = false;
        $currentDateTime = new \DateTime();
        $this->creationTime = $currentDateTime;
        $this->updatedTime = $currentDateTime;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): void
    {
        $this->destination = $destination;
    }

    public function clearSensitiveData(): void
    {
        $this->user = null;
        $this->destination = null;
    }

    public function __toString()
    {
        if ($this->source === null) {
            return '';
        }

        if ($this->random) {
            return $this->source . ' -> ' . $this->destination . ' (random)';
        }

        return $this->source . ' -> ' . $this->destination;
    }
}
