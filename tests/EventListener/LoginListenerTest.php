<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use App\EventListener\LoginListener;
use App\Handler\MailCryptKeyHandler;
use App\Service\UserLastLoginUpdateService;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListenerTest extends TestCase
{
    private UserLastLoginUpdateService $userLastLoginUpdateService;
    private LoggerInterface $logger;
    private LoginListener $listener;
    private LoginListener $listenerMailCrypt;
    private MailCryptKeyHandler $mailCryptKeyHandler;

    protected function setUp(): void
    {
        $this->userLastLoginUpdateService = $this->getMockBuilder(UserLastLoginUpdateService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mailCryptKeyHandler = $this->getMockBuilder(MailCryptKeyHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new LoginListener(
            $this->userLastLoginUpdateService,
            $this->mailCryptKeyHandler,
            $this->logger,
            2
        );
        // Enforces creation of mailCrypt on Login
        $this->listenerMailCrypt = new LoginListener(
            $this->userLastLoginUpdateService,
            $this->mailCryptKeyHandler,
            $this->logger,
            3
        );
    }

    /**
     * @dataProvider provider
     */
    public function testOnSecurityInteractiveLogin(User $user, bool $shouldCreateMailCryptKey): void
    {
        if ($shouldCreateMailCryptKey) {
            $this->mailCryptKeyHandler->expects($this->once())->method('create');
        } else {
            $this->mailCryptKeyHandler->expects($this->never())->method('create');
        }

        $this->userLastLoginUpdateService->expects($this->once())->method('updateLastLogin');

        $event = $this->getInteractiveEvent($user);

        $this->listenerMailCrypt->onSecurityInteractiveLogin($event);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|InteractiveLoginEvent
     */
    private function getInteractiveEvent(User $user): InteractiveLoginEvent
    {
        $request = new Request([], ['_password' => 'password']);

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
     * @dataProvider provider
     */
    public function testOnAuthenticationHandlerSuccess(User $user, bool $shouldCreateMailCryptKey): void
    {
        if ($shouldCreateMailCryptKey) {
            $this->mailCryptKeyHandler->expects($this->once())->method('create');
        } else {
            $this->mailCryptKeyHandler->expects($this->never())->method('create');
        }

        $this->userLastLoginUpdateService->expects($this->once())->method('updateLastLogin');

        $event = $this->getLoginEvent($user);

        $this->listenerMailCrypt->onAuthenticationHandlerSuccess($event);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|LoginEvent
     */
    private function getLoginEvent(User $user): LoginEvent
    {
        $event = $this->getMockBuilder(LoginEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->method('getUser')->willReturn($user);
        $event->method('getPlainPassword')->willReturn('password');

        return $event;
    }

    public static function provider(): array
    {
        $enableMailCrypt = [null, false, true];
        $shouldCreateMailCryptKeys = [true, true, false];

        return array_map(
            static function ($enable, $create) {
                $user = new User('test@example.org');
                if ($enable === false || $enable === true) {
                    $user->setMailCryptEnabled($enable);
                }

                return [$user, $create];
            },
            $enableMailCrypt,
            $shouldCreateMailCryptKeys
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
                LoginEvent::NAME => 'onAuthenticationHandlerSuccess',
            ],
            $this->listener::getSubscribedEvents()
        );
    }
}
