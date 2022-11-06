<?php

namespace App\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    private HttpUtils $httpUtils;
    private string $targetUrl;

    /**
     * Constructor.
     *
     * @param string $targetUrl
     */
    public function __construct(HttpUtils $httpUtils, string $targetUrl = '/')
    {
        $this->httpUtils = $httpUtils;
        $this->targetUrl = $targetUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function onLogoutSuccess(Request $request): Response
    {
        $request->getSession()->getFlashBag()->add('success', 'flashes.logout-successful');

        return $this->httpUtils->createRedirectResponse($request, $this->targetUrl);
    }
}
