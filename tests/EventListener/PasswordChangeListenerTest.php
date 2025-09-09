<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Enum\UserNotificationType;
use App\Event\UserEvent;
use App\EventListener\PasswordChangeListener;
use App\Helper\JsonRequestHelper;
use App\Repository\UserNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordChangeListenerTest extends TestCase
{
    private Security $security;
    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;
    private PasswordChangeListener $listener;

    protected function setUp(): void
    {
        $this->security = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->listener = new PasswordChangeListener(
            $this->security,
            $this->urlGenerator,
            $this->entityManager
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals([
            KernelEvents::REQUEST => [["onRequest", 0]],
            UserEvent::PASSWORD_CHANGED => [["onPasswordChanged", 0]],
        ], PasswordChangeListener::getSubscribedEvents());
    }

    public function testIgnoresSubRequests(): void
    {
        $request = Request::create('/some/path');

        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('isMainRequest')->willReturn(false);
        $event->expects($this->never())->method('setResponse');

        $this->listener->onRequest($event);
    }

    public function testReturnsWhenNotFullyAuthenticated(): void
    {
        $user = new User();
        $user->setPasswordChangeRequired(true);

        $this->security->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(false);

        $request = Request::create('/some/path');
        $request->attributes->set('_route', 'homepage');

        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $this->listener->onRequest($event);
    }

    public function testReturnsWhenUserDoesNotRequirePasswordChange(): void
    {
        $user = new User();
        $user->setPasswordChangeRequired(false);

        $this->security->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        $request = Request::create('/some/path');
        $request->attributes->set('_route', 'homepage');

        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $this->listener->onRequest($event);
    }

    /**
     * @dataProvider passwordRoutesProvider
     */
    public function testAllowsAccessToPasswordRoutes(string $routeName): void
    {
        $user = new User();
        $user->setPasswordChangeRequired(true);

        $this->security->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        $request = Request::create('/account/password');
        $request->attributes->set('_route', $routeName);

        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->never())->method('setResponse');

        $this->listener->onRequest($event);
    }

    public function passwordRoutesProvider(): array
    {
        return [
            ['account_password'],
            ['account_password_submit'],
        ];
    }

    public function testDeniesJsonRequests(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $user = new User();
        $user->setPasswordChangeRequired(true);

        $this->security->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        // JSON detection: path starts with /api/
        $request = Request::create('/api/v1/users', 'GET');
        $request->attributes->set('_route', 'api_users');

        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);

        $this->listener->onRequest($event);
    }

    public function testRedirectsToPasswordPage(): void
    {
        $user = new User();
        $user->setPasswordChangeRequired(true);

        $this->security->method('getUser')->willReturn($user);
        $this->security->method('isGranted')->with('IS_AUTHENTICATED_FULLY')->willReturn(true);

        $this->urlGenerator
            ->method('generate')
            ->with('account_password')
            ->willReturn('/account/password');

        $request = Request::create('/dashboard');
        $request->attributes->set('_route', 'dashboard');

        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);

        $event
            ->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof RedirectResponse
                    && $response->getTargetUrl() === '/account/password';
            }));

        $this->listener->onRequest($event);
    }

    public function testOnPasswordChanged(): void
    {
        $user = new User();
        $event = new UserEvent($user);
        $repo = $this->createMock(UserNotificationRepository::class);

        $this->entityManager->method('getRepository')->willReturn($repo);
        $repo->expects($this->once())
            ->method('deleteByUserAndType')
            ->with($user, UserNotificationType::PASSWORD_COMPROMISED);

        $this->listener->onPasswordChanged($event);
    }
}

