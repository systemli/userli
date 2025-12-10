<?php

declare(strict_types=1);

namespace App\Security\Badge;

use App\Entity\ApiToken;
use Override;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final readonly class ApiTokenBadge implements BadgeInterface
{
    public function __construct(private ApiToken $apiToken)
    {
    }

    public function getApiToken(): ApiToken
    {
        return $this->apiToken;
    }

    #[Override]
    public function isResolved(): bool
    {
        return true;
    }
}
