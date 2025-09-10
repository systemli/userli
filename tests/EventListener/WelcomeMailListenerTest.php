<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\UserEvent;
use App\EventListener\WelcomeMailListener;
use App\Message\WelcomeMail;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class WelcomeMailListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = WelcomeMailListener::getSubscribedEvents();
        $this->assertArrayHasKey(UserEvent::USER_CREATED, $events);
        $this->assertEquals('onUserCreated', $events[UserEvent::USER_CREATED]);
    }

    public function testOnUserCreatedDispatchesWelcomeMailWithLocale(): void
    {
        $user = new User();
        $user->setEmail('newuser@example.test');
        $locale = 'fr';

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->with('_locale')
            ->willReturn($locale);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($user, $locale) {
                return $message instanceof WelcomeMail
                    && $message->email === $user->getEmail()
                    && $message->locale === $locale;
            }));

        $listener = new WelcomeMailListener($requestStack, $bus);
        $listener->onUserCreated(new UserEvent($user));
    }
}
