<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\LoginEvent;
use App\EventListener\LoginListener;
use App\Handler\MailCryptKeyHandler;
use App\Service\UserLastLoginUpdateService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LoginListenerTest extends TestCase
{
    private Stub&UserLastLoginUpdateService $userLastLoginUpdateService;
    private Stub&LoggerInterface $logger;
    private LoginListener $listener;
    private LoginListener $listenerMailCrypt;
    private Stub&MailCryptKeyHandler $mailCryptKeyHandler;

    protected function setUp(): void
    {
        $this->userLastLoginUpdateService = $this->createStub(UserLastLoginUpdateService::class);
        $this->mailCryptKeyHandler = $this->createStub(MailCryptKeyHandler::class);
        $this->logger = $this->createStub(LoggerInterface::class);
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

    #[DataProvider('provider')]
    public function testOnSecurityInteractiveLogin(User $user, bool $shouldCreateMailCryptKey): void
    {
        $userLastLoginUpdateService = $this->createMock(UserLastLoginUpdateService::class);
        $mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);

        if ($shouldCreateMailCryptKey) {
            $mailCryptKeyHandler->expects($this->once())->method('create');
        } else {
            $mailCryptKeyHandler->expects($this->never())->method('create');
        }

        $userLastLoginUpdateService->expects($this->once())->method('updateLastLogin');

        $listenerMailCrypt = new LoginListener(
            $userLastLoginUpdateService,
            $mailCryptKeyHandler,
            $this->logger,
            3
        );

        $event = $this->getInteractiveEvent($user);

        $listenerMailCrypt->onSecurityInteractiveLogin($event);
    }

    private function getInteractiveEvent(User $user): InteractiveLoginEvent
    {
        $request = new Request([], ['_password' => 'password']);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $event = $this->createStub(InteractiveLoginEvent::class);

        $event->method('getRequest')->willReturn($request);
        $event->method('getAuthenticationToken')->willReturn($token);

        return $event;
    }

    #[DataProvider('provider')]
    public function testOnAuthenticationHandlerSuccess(User $user, bool $shouldCreateMailCryptKey): void
    {
        $userLastLoginUpdateService = $this->createMock(UserLastLoginUpdateService::class);
        $mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);

        if ($shouldCreateMailCryptKey) {
            $mailCryptKeyHandler->expects($this->once())->method('create');
        } else {
            $mailCryptKeyHandler->expects($this->never())->method('create');
        }

        $userLastLoginUpdateService->expects($this->once())->method('updateLastLogin');

        $listenerMailCrypt = new LoginListener(
            $userLastLoginUpdateService,
            $mailCryptKeyHandler,
            $this->logger,
            3
        );

        $event = $this->getLoginEvent($user);

        $listenerMailCrypt->onAuthenticationHandlerSuccess($event);
    }

    private function getLoginEvent(User $user): LoginEvent
    {
        $event = $this->createStub(LoginEvent::class);

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
        self::assertEquals(
            [
                SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
                LoginEvent::NAME => 'onAuthenticationHandlerSuccess',
            ],
            $this->listener::getSubscribedEvents()
        );
    }
}
