<?php

namespace App\Traits;

/**
 * @author louis <louis@systemli.org>
 */
trait PrivateKeyTrait
{
    /**
     * @var string|null
     */
    private $privateKey;

    /**
     * @return string|null
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * @param string|null $privateKey
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
    }
}
