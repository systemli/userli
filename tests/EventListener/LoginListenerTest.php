<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use App\EventListener\LoginListener;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListenerTest extends TestCase
{
    private EntityManagerInterface $manager;
    private PasswordUpdater $passwordUpdater;
    private LoginListener $listener;

    public function setUp(): void
    {
        $this->manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new LoginListener($this->manager, $this->passwordUpdater);
    }

    /**
     * @dataProvider provider
     */
    public function testOnSecurityInteractiveLogin(User $user, bool $update): void
    {
        $this->manager->expects($this->once())->method('flush');

        if ($update) {
            $this->passwordUpdater->expects($this->once())->method('updatePassword');
        } else {
            $this->passwordUpdater->expects($this->never())->method('updatePassword');
        }

        $event = $this->getEvent($user);

        $this->listener->onSecurityInteractiveLogin($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|InteractiveLoginEvent
     */
    private function getEvent(User $user): InteractiveLoginEvent
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->method('get')->willReturn('password');

        $token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token->method('getUser')->willReturn($user);

        $event = $this->getMockBuilder(InteractiveLoginEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->method('getRequest')->willReturn($request);
        $event->method('getAuthenticationToken')->willReturn($token);

        return $event;
    }

    public function provider(): array
    {
        return [
            [$this->getUser(null), true],
            [$this->getUser(0), true],
            [$this->getUser(1), true],
            [$this->getUser(2), false],
            [$this->getUser(3), false],
        ];
    }

    public function getUser(?int $passwordVersion): User
    {
        $user = new User();
        $user->setPasswordVersion($passwordVersion);

        return $user;
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals([
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            LoginEvent::class => 'onLogin',
        ],
            $this->listener::getSubscribedEvents());
    }
}
