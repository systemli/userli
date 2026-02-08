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
    public function testGetSubscribedEvents(): void
    {
        $events = CompromisedPasswordListener::getSubscribedEvents();

        self::assertArrayHasKey(SecurityEvents::INTERACTIVE_LOGIN, $events);
        self::assertEquals('onSecurityInteractiveLogin', $events[SecurityEvents::INTERACTIVE_LOGIN]);
        self::assertArrayHasKey(LoginEvent::NAME, $events);
        self::assertEquals('onLogin', $events[LoginEvent::NAME]);
    }

    public function testOnSecurityInteractiveLoginCallsCheckAndNotify(): void
    {
        $user = new User('user@example.org');
        $password = 'secret123';

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $request = new Request(request: ['_password' => $password]);
        $request->setLocale('de');

        $event = new InteractiveLoginEvent($request, $token);

        $service = $this->createMock(PasswordCompromisedService::class);
        $service->expects($this->once())
            ->method('checkAndNotify')
            ->with($user, $password, 'de');

        $listener = new CompromisedPasswordListener($service, $this->createStub(RequestStack::class));
        $listener->onSecurityInteractiveLogin($event);
    }

    public function testOnSecurityInteractiveLoginSkipsNonUserInstance(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $request = new Request();
        $event = new InteractiveLoginEvent($request, $token);

        $service = $this->createMock(PasswordCompromisedService::class);
        $service->expects($this->never())->method('checkAndNotify');

        $listener = new CompromisedPasswordListener($service, $this->createStub(RequestStack::class));
        $listener->onSecurityInteractiveLogin($event);
    }

    public function testOnSecurityInteractiveLoginSkipsWhenPasswordIsNull(): void
    {
        $user = new User('user@example.org');

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $request = new Request();
        $event = new InteractiveLoginEvent($request, $token);

        $service = $this->createMock(PasswordCompromisedService::class);
        $service->expects($this->never())->method('checkAndNotify');

        $listener = new CompromisedPasswordListener($service, $this->createStub(RequestStack::class));
        $listener->onSecurityInteractiveLogin($event);
    }

    public function testOnLoginCallsCheckAndNotify(): void
    {
        $user = new User('user@example.org');
        $password = 'secret123';

        $request = $this->createStub(Request::class);
        $request->method('getLocale')->willReturn('fr');

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $event = new LoginEvent($user, $password);

        $service = $this->createMock(PasswordCompromisedService::class);
        $service->expects($this->once())
            ->method('checkAndNotify')
            ->with($user, $password, 'fr');

        $listener = new CompromisedPasswordListener($service, $requestStack);
        $listener->onLogin($event);
    }

    public function testOnLoginUsesDefaultLocaleWhenNoRequest(): void
    {
        $user = new User('user@example.org');
        $password = 'secret123';
        $event = new LoginEvent($user, $password);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        $service = $this->createMock(PasswordCompromisedService::class);
        $service->expects($this->once())
            ->method('checkAndNotify')
            ->with($user, $password, 'en');

        $listener = new CompromisedPasswordListener($service, $requestStack, 'en');
        $listener->onLogin($event);
    }
}
