<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use App\EventListener\CompromisedPasswordListener;
use App\Service\PasswordCompromisedService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class CompromisedPasswordListenerTest extends TestCase
{
    private PasswordCompromisedService $passwordCompromisedService;
    private RequestStack $requestStack;
    private CompromisedPasswordListener $listener;

    protected function setUp(): void
    {
        $this->passwordCompromisedService = $this->createMock(PasswordCompromisedService::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->listener = new CompromisedPasswordListener(
            $this->passwordCompromisedService,
            $this->requestStack,
            'en'
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = CompromisedPasswordListener::getSubscribedEvents();

        $this->assertArrayHasKey(SecurityEvents::INTERACTIVE_LOGIN, $events);
        $this->assertArrayHasKey(LoginEvent::NAME, $events);
        $this->assertEquals('onSecurityInteractiveLogin', $events[SecurityEvents::INTERACTIVE_LOGIN]);
        $this->assertEquals('onLogin', $events[LoginEvent::NAME]);
    }

    public function testOnSecurityInteractiveLoginWithNonUserToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $request = $this->createMock(Request::class);
        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->method('getAuthenticationToken')->willReturn($token);
        $event->method('getRequest')->willReturn($request);

        // Service should not be called for non-user tokens
        $this->passwordCompromisedService->expects($this->never())->method('checkAndNotify');

        $this->listener->onSecurityInteractiveLogin($event);
    }

    public function testOnSecurityInteractiveLoginWithNoPassword(): void
    {
        $user = new User();
        $user->setEmail('test@example.org');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $request = $this->createMock(Request::class);
        $request->method('get')->with('_password')->willReturn(null);

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->method('getAuthenticationToken')->willReturn($token);
        $event->method('getRequest')->willReturn($request);

        // Service should not be called when no password is available
        $this->passwordCompromisedService->expects($this->never())->method('checkAndNotify');

        $this->listener->onSecurityInteractiveLogin($event);
    }

    public function testOnSecurityInteractiveLoginWithPassword(): void
    {
        $user = new User();
        $user->setEmail('test@example.org');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $request = $this->createMock(Request::class);
        $request->method('get')->with('_password')->willReturn('some_password');
        $request->method('getLocale')->willReturn('de');

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->method('getAuthenticationToken')->willReturn($token);
        $event->method('getRequest')->willReturn($request);

        // Service should be called with user, password and locale
        $this->passwordCompromisedService->expects($this->once())
            ->method('checkAndNotify')
            ->with($user, 'some_password', 'de');

        $this->listener->onSecurityInteractiveLogin($event);
    }

    public function testOnLoginWithNoPassword(): void
    {
        $user = new User();
        $user->setEmail('test@example.org');

        $event = $this->createMock(LoginEvent::class);
        $event->method('getUser')->willReturn($user);
        $event->method('getPlainPassword')->willReturn(null);

        // Service should not be called when no password is available
        $this->passwordCompromisedService->expects($this->never())->method('checkAndNotify');

        $this->listener->onLogin($event);
    }

    public function testOnLoginWithPassword(): void
    {
        $user = new User();
        $user->setEmail('test@example.org');

        $request = $this->createMock(Request::class);
        $request->method('getLocale')->willReturn('fr');

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $event = $this->createMock(LoginEvent::class);
        $event->method('getUser')->willReturn($user);
        $event->method('getPlainPassword')->willReturn('test_password');

        // Service should be called with user, password and locale from request
        $this->passwordCompromisedService->expects($this->once())
            ->method('checkAndNotify')
            ->with($user, 'test_password', 'fr');

        $this->listener->onLogin($event);
    }

    public function testOnLoginWithPasswordAndNoRequest(): void
    {
        $user = new User();
        $user->setEmail('test@example.org');

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $event = $this->createMock(LoginEvent::class);
        $event->method('getUser')->willReturn($user);
        $event->method('getPlainPassword')->willReturn('test_password');

        // Service should be called with user, password and default locale when no request
        $this->passwordCompromisedService->expects($this->once())
            ->method('checkAndNotify')
            ->with($user, 'test_password', 'en');

        $this->listener->onLogin($event);
    }
}
