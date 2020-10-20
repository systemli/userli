<?php

namespace App\Tests\Handler;

use App\Handler\LogoutSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class LogoutSuccessHandlerTest extends TestCase
{
    public function testLogout(): void
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $session = new Session(new MockArraySessionStorage());

        $request
            ->method('getSession')
            ->willReturn($session);

        $response = new Response();
        $httpUtils = $this->getMockBuilder('Symfony\Component\Security\Http\HttpUtils')->getMock();
        $httpUtils->expects(self::once())
            ->method('createRedirectResponse')
            ->with($request, '/')
            ->willReturn($response);

        $handler = new LogoutSuccessHandler($httpUtils, '/');
        $result = $handler->onLogoutSuccess($request);

        self::assertSame($response, $result);
        self::assertArrayHasKey('success', $request->getSession()->getFlashBag()->all());
    }
}
