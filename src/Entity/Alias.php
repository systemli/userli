<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\DeleteTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\IdTrait;
use App\Traits\UpdatedTimeTrait;
use App\Traits\UserAwareTrait;

/**
 * @author louis <louis@systemli.org>
 */
class Alias
{
    use IdTrait, CreationTimeTrait, UpdatedTimeTrait, DeleteTrait, DomainAwareTrait, UserAwareTrait;

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
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * {@inheritdoc}
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * {@inheritdoc}
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }
}
