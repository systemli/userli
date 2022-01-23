<?php

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\EmailTrait;
use App\Traits\IdTrait;
use App\Traits\OpenPgpKeyTrait;
use App\Traits\UpdatedTimeTrait;
use App\Traits\UserAwareTrait;

class OpenPgpKey
{
    use CreationTimeTrait;
    use UpdatedTimeTrait;
    use IdTrait;
    use UserAwareTrait;
    use EmailTrait;
    use OpenPgpKeyTrait;

    /**
     * OpenPgpKey constructor.
     */
    public function __construct()
    {
        $currentDateTime = new \DateTime();
        $this->creationTime = $currentDateTime;
        $this->updatedTime = $currentDateTime;
    }

    public function toBinary(): ?string
    {
        return ($this->getKeyData()) ? base64_decode($this->getKeyData()) : null;
    }
}
