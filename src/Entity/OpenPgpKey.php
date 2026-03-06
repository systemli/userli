<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OpenPgpKeyRepository;
use App\Traits\EmailTrait;
use App\Traits\IdTrait;
use App\Traits\OpenPgpKeyTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpenPgpKeyRepository::class)]
#[ORM\Table(name: 'openpgp_keys')]
#[ORM\Index(name: 'idx_wkd_hash', columns: ['wkd_hash'])]
class OpenPgpKey
{
    use EmailTrait;
    use IdTrait;
    use OpenPgpKeyTrait;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'uploadedKeys')]
    #[ORM\JoinColumn(name: 'uploader_id', onDelete: 'SET NULL')]
    private ?User $uploader = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $wkdHash = null;

    public function getUploader(): ?User
    {
        return $this->uploader;
    }

    public function setUploader(?User $uploader): void
    {
        $this->uploader = $uploader;
    }

    public function getWkdHash(): ?string
    {
        return $this->wkdHash;
    }

    public function setWkdHash(?string $wkdHash): void
    {
        $this->wkdHash = $wkdHash;
    }

    public function toBinary(): ?string
    {
        return ($this->getKeyData()) ? base64_decode($this->getKeyData()) : null;
    }
}
