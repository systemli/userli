<?php

namespace App\Form\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OpenPgpKey
{
    /**
     * @ORM\Column(type="string")
     */
    private $keyFile;

    /** @var string */
    private $keyText;

    /**
     * @return string|null
     */
    public function getKeyFile(): ?string
    {
        return $this->keyFile;
    }

    /**
     * @param string $keyFile
     *
     * @return $this
     */
    public function setKeyFile(string $keyFile): self
    {
        $this->keyFile = $keyFile;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKeyText(): ?string
    {
        return $this->keyText;
    }

    /**
     * @param string $keyText
     *
     * @return $this
     */
    public function setKeyText(string $keyText): self
    {
        $this->keyText = $keyText;

        return $this;
    }
}
