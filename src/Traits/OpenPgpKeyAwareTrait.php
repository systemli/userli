<?php

namespace App\Traits;

use App\Entity\OpenPgpKey;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait OpenPgpKeyAwareTrait
{
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: OpenPgpKey::class)]
    private Collection $openPgpKeys;

    public function __construct()
    {
        $this->openPgpKeys = new ArrayCollection();
    }

    public function getOpenPgpKeys(): Collection
    {
        return $this->openPgpKeys;
    }

    public function setOpenPgpKeys(Collection $openPgpKeys): void
    {
        $this->openPgpKeys = $openPgpKeys;
    }

    public function addOpenPgpKey(OpenPgpKey $openPgpKey): void
    {
        if (!$this->openPgpKeys->contains($openPgpKey)) {
            $this->openPgpKeys->add($openPgpKey);
            $openPgpKey->setUser($this);
        }
    }

    public function removeOpenPgpKey(OpenPgpKey $openPgpKey): void
    {
        if ($this->openPgpKeys->removeElement($openPgpKey)) {
            if ($openPgpKey->getUser() === $this) {
                $openPgpKey->setUser(null);
            }
        }
    }

    public function hasOpenPgpKeys(): bool
    {
        return !$this->openPgpKeys->isEmpty();
    }
}
