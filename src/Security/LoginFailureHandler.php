<?php

declare(strict_types=1);

namespace App\Security;

use App\Controller\SecurityController;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Renders the login page with HTTP 401 instead of redirecting (302),
 * so failed logins can be counted by status code.
 */
final readonly class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(private HttpKernelInterface $httpKernel)
    {
    }

    #[Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->attributes->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        $subRequest = $request->duplicate(null, null, array_merge($request->attributes->all(), [
            '_route' => 'login',
            '_controller' => SecurityController::class.'::login',
        ]));
        $subRequest->setMethod('GET');

        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $response->setStatusCode(Response::HTTP_UNAUTHORIZED);

        return $response;
    }
}
