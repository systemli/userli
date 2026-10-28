<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Alias;
use App\Entity\User;
use App\Event\AliasCreatedEvent;
use App\EventListener\AliasCreationListener;
use App\Mail\AliasCreatedMailer;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AliasCreationListenerTest extends TestCase
{
    use EventListenerAttributes;

    public function testListensToEvents(): void
    {
        self::assertEquals([
            AliasCreatedEvent::NAME => ['onAliasCreated', 0],
        ], self::listenedEvents(AliasCreationListener::class));
    }

    public function testOnAliasCreatedSendsMessageWithSessionLocale(): void
    {
        $user = new User('user@example.org');
        $alias = new Alias();
        $alias->setSource('alias@example.org');
        $alias->setUser($user);

        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturn('de');

        $request = $this->createStub(Request::class);
        $request->method('getSession')->willReturn($session);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $mailer = $this->createMock(AliasCreatedMailer::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($user, $alias, 'de');

        $listener = new AliasCreationListener($requestStack, $mailer, 'en');
        $listener->onAliasCreated(new AliasCreatedEvent($alias));
    }

    public function testOnAliasCreatedThrowsExceptionWhenUserIsNull(): void
    {
        $alias = new Alias();
        $alias->setSource('alias@example.org');
        // User is null

        $requestStack = $this->createStub(RequestStack::class);
        $mailer = $this->createStub(AliasCreatedMailer::class);

        $listener = new AliasCreationListener($requestStack, $mailer, 'en');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User should not be null');
        $listener->onAliasCreated(new AliasCreatedEvent($alias));
    }
}
