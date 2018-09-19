<?php

namespace App\Traits;

/**
 * @author louis <louis@systemli.org>
 */
trait NameTrait
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
