<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Enum\ApiScope;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ApiToken
{
    #[NotBlank]
    #[Length(min: 5, max: 32)]
    private string $name;

    #[Choice(callback: [ApiScope::class, 'all'], multiple: true)]
    #[Count(min: 1)]
    private array $scopes = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScopes(array $scopes): void
    {
        $this->scopes = $scopes;
    }
}
