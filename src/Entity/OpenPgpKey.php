<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Traits\EmailTrait;
use App\Traits\IdTrait;
use App\Traits\OpenPgpKeyTrait;
use App\Traits\UserAwareTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OpenPgpKeyRepository")
 * @ORM\Table(name="virtual_openpgp_keys")
 * @ApiResource(
 *     normalizationContext={"enable_max_depth"=true},
 *     security="is_granted('ROLE_USER')",
 *     collectionOperations={
 *         "get"={"security"="is_granted('ROLE_USER')"},
 *         "post"={"security"="is_granted('ROLE_ADMIN')"},
 *     },
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_ADMIN') or object.getUser() == user"},
 *         "put"={"security"="is_granted('ROLE_ADMIN') or object.getUser() == user"},
 *         "delete"={"security"="is_granted('ROLE_ADMIN') or object.getUser() == user"},
 *     },
 * )
 */
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
