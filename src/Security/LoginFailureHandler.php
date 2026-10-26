<?php

declare(strict_types=1);

namespace App\Security;

use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Renders the given form route with HTTP 401 instead of redirecting (302),
 * so failed logins can be counted by status code.
 */
final readonly class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    /**
     * @param string $route          Route of the form to render on failure
     * @param bool   $errorInSession Whether the form controller reads the error from the session
     */
    public function __construct(
        private HttpKernelInterface $httpKernel,
        private RouterInterface $router,
        private string $route,
        private bool $errorInSession = false,
    ) {
    }

    #[Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->attributes->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        if ($this->errorInSession) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        $subRequest = $request->duplicate(null, null, array_merge(
            $request->attributes->all(),
            $this->router->match($this->router->generate($this->route)),
        ));
        $subRequest->setMethod('GET');

        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $response->setStatusCode(Response::HTTP_UNAUTHORIZED);

        return $response;
    }
}
