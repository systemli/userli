<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Validator\EmailAllowedSymbols;
use App\Validator\EmailAvailable;
use App\Validator\EmailDomain;
use App\Validator\EmailLength;
use Symfony\Component\Validator\Constraints as Assert;

final class AliasCreate
{
    /**
     * Full email address (alias@domain).
     * The form appends the user's domain to the submitted local part.
     */
    #[Assert\NotBlank]
    #[EmailAvailable]
    #[EmailAllowedSymbols]
    #[EmailDomain]
    #[EmailLength(minLength: 3, maxLength: 32)]
    private string $alias;

    #[Assert\Length(max: 40, groups: ['Default', 'random'])]
    private ?string $note = null;

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note !== null ? trim($note) : null;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }
}
