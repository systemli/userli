<?php

namespace App\Tests\Handler;

use App\Handler\LogoutSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class LogoutSuccessHandlerTest extends TestCase
{
    public function testLogout()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $session = new Session(new MockArraySessionStorage());

        $request->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($session));

        $response = new Response();
        $httpUtils = $this->getMockBuilder('Symfony\Component\Security\Http\HttpUtils')->getMock();
        $httpUtils->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, '/')
            ->will($this->returnValue($response));

        $handler = new LogoutSuccessHandler($httpUtils, '/');
        $result = $handler->onLogoutSuccess($request);

        $this->assertSame($response, $result);
        $this->assertArrayHasKey('success', $request->getSession()->getFlashBag()->all());
    }
}
