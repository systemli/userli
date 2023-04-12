<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use App\EventListener\LoginListener;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListenerTest extends TestCase
{
    private LoginListener $listener;

    public function setUp(): void
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new LoginListener($manager, $passwordUpdater);
    }

    public function testOnSecurityInteractiveLogin(): void
    {
        $user = new User();
        $user->setLastLoginTime(new \DateTime('1970-01-01 00:00:00'));

        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->method('get')->willReturn('password');
        $authenticationToken = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authenticationToken->method('getUser')->willReturn($user);
        $event = new InteractiveLoginEvent($request, $authenticationToken);

        $this->listener->onSecurityInteractiveLogin($event);

        self::assertNotEquals(new \DateTime('1970-01-01 00:00:00'), $user->getLastLoginTime());

        $user = new User();
        $user->setLastLoginTime(new \DateTime('1970-01-01 00:00:00'));
        $authenticationToken->method('getUser')->willReturn(null);

        $event = new InteractiveLoginEvent($request, $authenticationToken);

        $this->listener->onSecurityInteractiveLogin($event);

        self::assertEquals(new \DateTime('1970-01-01 00:00:00'), $user->getLastLoginTime());
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
                LoginEvent::class => 'onLogin',
            ],
            $this->listener::getSubscribedEvents()
        );
    }
}
