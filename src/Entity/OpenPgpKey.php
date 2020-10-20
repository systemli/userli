<?php

namespace App\Entity;

use App\Traits\EmailTrait;
use App\Traits\IdTrait;
use App\Traits\OpenPgpKeyTrait;
use App\Traits\UserAwareTrait;

class OpenPgpKey
{
    use IdTrait;
    use UserAwareTrait;
    use EmailTrait;
    use OpenPgpKeyTrait;

    /**
     * @return string|null
     */
    public function toBinary(): ?string
    {
        return ($this->getKeyData()) ? base64_decode($this->getKeyData()) : null;
    }
}
