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
     * @return null|string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param null|string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }
}
