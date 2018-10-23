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
class Alias implements SoftDeletableInterface
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
     * @var bool
     */
    protected $random;

    /**
     * @var int
     */
    protected $type;

    /**
     * Alias constructor.
     */
    public function __construct()
    {
        $this->deleted = false;
        $this->random = false;
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

    public function setEmptyUser()
    {
        $this->user = null;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isRandom()
    {
        return $this->random;
    }

    public function setRandom()
    {
        $this->random = true;
    }
}
