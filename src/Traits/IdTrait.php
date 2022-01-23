<?php

namespace App\Traits;

trait IdTrait
{
    /**
     * @var int
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
