<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class RetentionAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private string $retentionAccessToken)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        if ($accessToken !== $this->retentionAccessToken) {
            throw new BadCredentialsException('Invalid access token');
        }

        return new UserBadge('retention');
    }
}
