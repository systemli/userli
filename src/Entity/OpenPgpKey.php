<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OpenPgpKeyRepository;
use App\Traits\EmailTrait;
use App\Traits\IdTrait;
use App\Traits\OpenPgpKeyTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpenPgpKeyRepository::class)]
#[ORM\Table(name: 'virtual_openpgp_keys')]
class OpenPgpKey
{
    use EmailTrait;
    use IdTrait;
    use OpenPgpKeyTrait;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'openPgpKeys')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function toBinary(): ?string
    {
        return ($this->getKeyData()) ? base64_decode($this->getKeyData()) : null;
    }
}
