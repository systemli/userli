<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

readonly class PostfixAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private string $postfixApiAccessToken)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        if ($accessToken !== $this->postfixApiAccessToken) {
            throw new BadCredentialsException('Invalid access token');
        }

        return new UserBadge('postfix');
    }
}
