<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class KeycloakApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private string $keycloakApi, private string $keycloakApiAccessToken)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge {
        if (!$this->keycloakApi) {
            throw new BadCredentialsException('API disabled');
        }

        if (!($accessToken === $this->keycloakApiAccessToken)) {
            throw new BadCredentialsException('Invalid access token');
        }

        return new UserBadge('keycloak');
    }
}
