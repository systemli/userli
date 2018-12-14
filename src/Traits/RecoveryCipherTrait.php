<?php

namespace App\Traits;

/**
 * @author doobry <doobry@systemli.org>
 */
trait RecoveryCipherTrait
{
    /**
     * @var string|null
     */
    private $recoveryCipher;

    /**
     * @return string|null
     */
    public function getRecoveryCipher()
    {
        return $this->recoveryCipher;
    }

    /**
     * @param string $recoveryCipher
     */
    public function setRecoveryCipher($recoveryCipher)
    {
        $this->recoveryCipher = $recoveryCipher;
    }
}
