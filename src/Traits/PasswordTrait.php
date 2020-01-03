<?php

namespace App\Traits;

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
    public function setPassword(string $password)
    {
        $this->password = $password;
    }
}
