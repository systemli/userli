<?php

namespace App\Entity;

use App\Repository\OpenPgpKeyRepository;
use App\Traits\EmailTrait;
use App\Traits\IdTrait;
use App\Traits\OpenPgpKeyTrait;
use App\Traits\UserAwareTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpenPgpKeyRepository::class)]
#[ORM\Table(name: 'virtual_openpgp_keys')]
class OpenPgpKey
{
    use IdTrait;
    use UserAwareTrait;
    use EmailTrait;
    use OpenPgpKeyTrait;

    public function toBinary(): ?string
    {
        return ($this->getKeyData()) ? base64_decode($this->getKeyData()) : null;
    }
}
