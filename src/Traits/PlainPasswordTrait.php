<?php

namespace App\Traits;

/**
 * Trait PlainPasswordTrait.
 */
trait PlainPasswordTrait
{
    /**
     * @var string|null
     */
    private $plainPassword;

    /**
     * @return string|null
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }
}
