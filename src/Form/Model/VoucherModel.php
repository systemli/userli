<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Domain;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class VoucherModel
{
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 6)]
    private ?string $code = null;

    #[Assert\NotNull]
    private ?User $user = null;

    #[Assert\NotNull]
    private ?Domain $domain = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): void
    {
        $this->domain = $domain;
    }
}
