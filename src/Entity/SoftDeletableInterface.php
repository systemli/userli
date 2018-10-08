<?php

namespace App\Entity;

interface SoftDeletableInterface
{
    /**
     * @return mixed
     */
    public function isDeleted();
}
