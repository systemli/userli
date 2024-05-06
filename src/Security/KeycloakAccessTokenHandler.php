<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class KeycloakAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private string $keycloakApiAccessToken)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge {
        if ($accessToken !== $this->keycloakApiAccessToken) {
            throw new BadCredentialsException('Invalid access token');
        }

        return new UserBadge('keycloak');
    }
}
