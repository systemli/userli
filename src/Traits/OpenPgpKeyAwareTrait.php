<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\OpenPgpKey;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait OpenPgpKeyAwareTrait
{
    #[ORM\OneToMany(mappedBy: 'uploader', targetEntity: OpenPgpKey::class)]
    private Collection $uploadedKeys;

    public function __construct()
    {
        $this->uploadedKeys = new ArrayCollection();
    }

    public function getUploadedKeys(): Collection
    {
        return $this->uploadedKeys;
    }

    public function setUploadedKeys(Collection $uploadedKeys): void
    {
        $this->uploadedKeys = $uploadedKeys;
    }

    public function addUploadedKey(OpenPgpKey $openPgpKey): void
    {
        if (!$this->uploadedKeys->contains($openPgpKey)) {
            $this->uploadedKeys->add($openPgpKey);
            $openPgpKey->setUploader($this);
        }
    }

    public function removeUploadedKey(OpenPgpKey $openPgpKey): void
    {
        if ($this->uploadedKeys->removeElement($openPgpKey)) {
            if ($openPgpKey->getUploader() === $this) {
                $openPgpKey->setUploader(null);
            }
        }
    }

    public function hasUploadedKeys(): bool
    {
        return !$this->uploadedKeys->isEmpty();
    }
}
