<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Event\AliasEvent;
use App\EventListener\AliasListener;
use App\Repository\AliasRepository;
use App\Sender\AliasCreatedMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AliasListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = AliasListener::getSubscribedEvents();

        self::assertArrayHasKey(AliasEvent::CUSTOM_CREATED, $events);
        self::assertEquals('onCustomCreated', $events[AliasEvent::CUSTOM_CREATED]);

        self::assertArrayHasKey(AliasEvent::RANDOM_CREATED, $events);
        self::assertEquals('onRandomCreated', $events[AliasEvent::RANDOM_CREATED]);
    }

    public function testOnCustomCreatedSendsMessageWithSessionLocale(): void
    {
        $user = new User('user@example.org');
        $alias = new Alias();
        $alias->setSource('alias@example.org');
        $alias->setUser($user);

        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->with('_locale', 'en')->willReturn('de');

        $request = $this->createStub(Request::class);
        $request->method('getSession')->willReturn($session);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $sender = $this->createMock(AliasCreatedMessageSender::class);
        $sender->expects($this->once())
            ->method('send')
            ->with($user, $alias, 'de');

        $manager = $this->createStub(EntityManagerInterface::class);

        $listener = new AliasListener($requestStack, $sender, $manager, 'en');
        $listener->onCustomCreated(new AliasEvent($alias));
    }

    public function testOnCustomCreatedThrowsExceptionWhenUserIsNull(): void
    {
        $alias = new Alias();
        $alias->setSource('alias@example.org');
        // User is null

        $requestStack = $this->createStub(RequestStack::class);
        $sender = $this->createStub(AliasCreatedMessageSender::class);
        $manager = $this->createStub(EntityManagerInterface::class);

        $listener = new AliasListener($requestStack, $sender, $manager, 'en');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User should not be null');
        $listener->onCustomCreated(new AliasEvent($alias));
    }

    public function testOnRandomCreatedDoesNothingWhenNoCollision(): void
    {
        $alias = new Alias();
        $alias->setSource('random123@example.org');

        $repository = $this->createStub(AliasRepository::class);
        $repository->method('findOneBySource')->willReturn(null);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $requestStack = $this->createStub(RequestStack::class);
        $sender = $this->createStub(AliasCreatedMessageSender::class);

        $listener = new AliasListener($requestStack, $sender, $manager, 'en');
        $listener->onRandomCreated(new AliasEvent($alias));

        self::assertSame('random123@example.org', $alias->getSource());
    }

    public function testOnRandomCreatedRegeneratesSourceOnCollision(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $alias = new Alias();
        $alias->setSource('collision@example.org');
        $alias->setDomain($domain);

        $existingAlias = new Alias();
        $callCount = 0;

        $repository = $this->createStub(AliasRepository::class);
        $repository->method('findOneBySource')->willReturnCallback(
            static function () use (&$callCount, $existingAlias) {
                ++$callCount;

                // First call returns existing alias (collision), second returns null (no collision)
                return $callCount === 1 ? $existingAlias : null;
            }
        );

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $requestStack = $this->createStub(RequestStack::class);
        $sender = $this->createStub(AliasCreatedMessageSender::class);

        $listener = new AliasListener($requestStack, $sender, $manager, 'en');
        $listener->onRandomCreated(new AliasEvent($alias));

        // Source should have been regenerated
        self::assertNotSame('collision@example.org', $alias->getSource());
        self::assertStringEndsWith('@example.org', $alias->getSource());
    }
}
