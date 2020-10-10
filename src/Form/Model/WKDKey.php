<?php

namespace App\Form\Model;

use Doctrine\ORM\Mapping as ORM;

class WKDKey
{
    /**
     * @ORM\Column(type="string")
     */
    private $keyFile;

    /** @var string */
    private $keyText;

    public function getKeyFile(): ?string
    {
        return $this->keyFile;
    }

    /**
     * @return $this
     */
    public function setKeyFile(string $keyFile): self
    {
        $this->keyFile = $keyFile;

        return $this;
    }

    public function getKeyText(): ?string
    {
        return $this->keyText;
    }

    /**
     * @return $this
     */
    public function setKeyText(string $keyText): self
    {
        $this->keyText = $keyText;

        return $this;
    }
}
