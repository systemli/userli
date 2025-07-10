<?php

namespace App\Security;

use SensitiveParameter;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private string $accessTokenDovecot,
        private string $accessTokenKeycloak,
        private string $accessTokenPostfix,
        private string $accessTokenRetention,
    ) {}

    public function getUserBadgeFrom(#[SensitiveParameter] string $accessToken): UserBadge
    {
        switch ($accessToken) {
            case $this->accessTokenDovecot:
                return new UserBadge('dovecot');
            case $this->accessTokenKeycloak:
                return new UserBadge('keycloak');
            case $this->accessTokenRetention:
                return new UserBadge('retention');
            case $this->accessTokenPostfix:
                return new UserBadge('postfix');
            default:
                throw new BadCredentialsException('Invalid access token');
        }
    }
}
