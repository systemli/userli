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
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
