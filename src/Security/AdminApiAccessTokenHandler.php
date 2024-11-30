<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AdminApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private string $adminApiAccessToken)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        if ($accessToken !== $this->adminApiAccessToken) {
            throw new BadCredentialsException('Invalid access token');
        }

        return new UserBadge('admin_api');
    }
}
