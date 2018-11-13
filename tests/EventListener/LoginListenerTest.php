<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\LoginListener;
use App\Helper\PasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * @author tim <tim@systemli.org>
 */
class LoginListenerTest extends TestCase
{
    /**
     * @dataProvider provider
     *
     * @param User $user
     * @param bool $update
     */
    public function testOnSecurityInteractiveLogin(User $user, bool $update)
    {
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($update) {
            $passwordUpdater->expects($this->once())->method('updatePassword');
        } else {
            $passwordUpdater->expects($this->never())->method('updatePassword');
        }

        $event = $this->getEvent($user);

        $listener = new LoginListener($manager, $passwordUpdater);
        $listener->onSecurityInteractiveLogin($event);
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
     * @param int|null $passwordVersion
     *
     * @return User
     */
    public function getUser(?int $passwordVersion)
    {
        $user = new User();
        $user->setPasswordVersion($passwordVersion);

        return $user;
    }
}
