<?php

declare(strict_types=1);

namespace App\Security\Badge;

use App\Entity\ApiToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

readonly class ApiTokenBadge implements BadgeInterface
{
    public function __construct(private ApiToken $apiToken)
    {

    }

    public function getApiToken(): ApiToken
    {
        return $this->apiToken;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
