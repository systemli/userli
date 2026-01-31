<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class AliasCreate
{
    #[Assert\NotNull]
    #[Assert\Length(min: 3, max: 24)]
    public string $alias;

    #[Assert\Length(max: 40)]
    private ?string $note = null;

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note !== null ? trim($note) : null;
    }
}
