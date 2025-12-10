<?php

declare(strict_types=1);

namespace App\Security;

use App\Security\Badge\ApiTokenBadge;
use App\Service\ApiTokenManager;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly ApiTokenManager $apiTokenManager)
    {
    }

    #[Override]
    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/api/');
    }

    #[Override]
    public function authenticate(Request $request): Passport
    {
        $plainToken = $this->extractToken($request);

        if (null === $plainToken || '' === $plainToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $token = $this->apiTokenManager->findOne($plainToken);
        if (null === $token) {
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }

        $this->apiTokenManager->updateLastUsedTime($token);

        $request->attributes->set('api_token', $token);

        return new SelfValidatingPassport(
            new UserBadge('api_user'),
            [new ApiTokenBadge($token)]
        );
    }

    #[Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    #[Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function extractToken(Request $request): ?string
    {
        // Authorization Header (Bearer Token)
        $authorizationHeader = $request->headers->get('Authorization');
        if ($authorizationHeader && preg_match('/Bearer\s+(.+)/i', $authorizationHeader, $matches)) {
            return $matches[1];
        }

        // X-API-Token Header
        return $request->headers->get('X-API-Token');
    }
}
