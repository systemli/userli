<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Validator\EmailAllowedSymbols;
use App\Validator\EmailAvailable;
use App\Validator\EmailDomain;
use Symfony\Component\Validator\Constraints as Assert;

final class AliasCreate
{
    /**
     * Full email address (alias@domain).
     * The form combines the local part with the domain before submission.
     */
    #[Assert\NotNull]
    #[EmailAvailable]
    #[EmailAllowedSymbols]
    #[EmailDomain]
    private string $alias;

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

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }
}
