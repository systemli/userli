<?php

namespace AppBundle\Traits;

/**
 * @author louis <louis@systemli.org>
 */
trait PasswordTrait
{
    /**
     * @var string|null
     */
    private $password;

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
}
