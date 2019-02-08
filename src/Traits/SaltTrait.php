<?php

namespace App\Traits;

/**
 * @author louis <louis@systemli.org>
 */
trait SaltTrait
{
    /**
     * @var string|null
     */
    private $salt;

    /**
     * @return string|null
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string|null $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }
}
