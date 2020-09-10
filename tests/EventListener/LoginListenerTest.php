<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use App\EventListener\LoginListener;
use App\Helper\PasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListenerTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $manager;
    /**
     * @var MockObject
     */
    private $passwordUpdater;
    /**
     * @var LoginListener
     */
    private $listener;

    public function setUp(): void
    {
        $this->manager = $this->getMockBuilder(ObjectManager::class)
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
    public function testOnSecurityInteractiveLogin(User $user, bool $update)
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
     * @param User $user
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|InteractiveLoginEvent
     */
    private function getEvent($user)
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())->method('get')->willReturn('password');

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

    /**
     * @return array
     */
    public function provider()
    {
        return [
            [$this->getUser(null), true],
            [$this->getUser(0), true],
            [$this->getUser(1), true],
            [$this->getUser(2), false],
            [$this->getUser(3), false],
        ];
    }

    /**
     * @return User
     */
    public function getUser(?int $passwordVersion)
    {
        $user = new User();
        $user->setPasswordVersion($passwordVersion);

        return $user;
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals($this->listener->getSubscribedEvents(),
            [SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
                LoginEvent::NAME => 'onLogin', ]);
    }
}
