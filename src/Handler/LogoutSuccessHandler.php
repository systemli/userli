<?php

namespace App\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /**
     * @var HttpUtils
     */
    private $httpUtils;
    /**
     * @var string
     */
    private $targetUrl;

    /**
     * Constructor.
     *
     * @param HttpUtils $httpUtils
     * @param string    $targetUrl
     */
    public function __construct(HttpUtils $httpUtils, $targetUrl = '/')
    {
        $this->httpUtils = $httpUtils;
        $this->targetUrl = $targetUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function onLogoutSuccess(Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', 'flashes.logout-successful');

        return $this->httpUtils->createRedirectResponse($request, $this->targetUrl);
    }
}
