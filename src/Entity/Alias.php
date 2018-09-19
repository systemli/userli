<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\DomainAwareTrait;
use App\Traits\IdTrait;
use App\Traits\UpdatedTimeTrait;

/**
 * @author louis <louis@systemli.org>
 */
class Alias
{
    use IdTrait, CreationTimeTrait, UpdatedTimeTrait, DomainAwareTrait;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $destination;

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
