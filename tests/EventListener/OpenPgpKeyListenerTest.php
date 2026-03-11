<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Alias;
use App\Entity\User;
use App\Event\AliasDeletedEvent;
use App\Event\UserEvent;
use App\EventListener\OpenPgpKeyListener;
use App\Repository\AliasRepository;
use App\Repository\UserRepository;
use App\Service\OpenPgpKeyManager;
use PHPUnit\Framework\TestCase;

class OpenPgpKeyListenerTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = OpenPgpKeyListener::getSubscribedEvents();

        self::assertArrayHasKey(AliasDeletedEvent::CUSTOM, $events);
        self::assertArrayHasKey(UserEvent::USER_DELETED, $events);
        self::assertEquals('onCustomAliasDeleted', $events[AliasDeletedEvent::CUSTOM]);
        self::assertEquals('onUserDeleted', $events[UserEvent::USER_DELETED]);
    }

    public function testOnAliasDeletedCleansUpOrphanedKey(): void
    {
        $alias = new Alias();
        $alias->setSource('shared@example.org');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('existsByEmail')->willReturn(false);

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findOneBySource')->willReturn(null);

        $openPgpKeyManager = $this->createMock(OpenPgpKeyManager::class);
        $openPgpKeyManager->expects(self::once())->method('deleteKey')->with('shared@example.org');

        $listener = new OpenPgpKeyListener($openPgpKeyManager, $userRepository, $aliasRepository);
        $listener->onCustomAliasDeleted(new AliasDeletedEvent($alias));
    }

    public function testOnAliasDeletedKeepsKeyWhenUserExists(): void
    {
        $alias = new Alias();
        $alias->setSource('shared@example.org');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('existsByEmail')->willReturn(true);

        $aliasRepository = $this->createStub(AliasRepository::class);

        $openPgpKeyManager = $this->createMock(OpenPgpKeyManager::class);
        $openPgpKeyManager->expects(self::never())->method('deleteKey');

        $listener = new OpenPgpKeyListener($openPgpKeyManager, $userRepository, $aliasRepository);
        $listener->onCustomAliasDeleted(new AliasDeletedEvent($alias));
    }

    public function testOnAliasDeletedKeepsKeyWhenAnotherAliasExists(): void
    {
        $alias = new Alias();
        $alias->setSource('shared@example.org');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('existsByEmail')->willReturn(false);

        $otherAlias = new Alias();
        $otherAlias->setSource('shared@example.org');

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findOneBySource')->willReturn($otherAlias);

        $openPgpKeyManager = $this->createMock(OpenPgpKeyManager::class);
        $openPgpKeyManager->expects(self::never())->method('deleteKey');

        $listener = new OpenPgpKeyListener($openPgpKeyManager, $userRepository, $aliasRepository);
        $listener->onCustomAliasDeleted(new AliasDeletedEvent($alias));
    }

    public function testOnAliasDeletedSkipsNullSource(): void
    {
        $alias = new Alias();

        $openPgpKeyManager = $this->createMock(OpenPgpKeyManager::class);
        $openPgpKeyManager->expects(self::never())->method('deleteKey');

        $userRepository = $this->createStub(UserRepository::class);
        $aliasRepository = $this->createStub(AliasRepository::class);

        $listener = new OpenPgpKeyListener($openPgpKeyManager, $userRepository, $aliasRepository);
        $listener->onCustomAliasDeleted(new AliasDeletedEvent($alias));
    }

    public function testOnUserDeletedCleansUpOrphanedKey(): void
    {
        $user = new User('alice@example.org');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('existsByEmail')->willReturn(false);

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findOneBySource')->willReturn(null);

        $openPgpKeyManager = $this->createMock(OpenPgpKeyManager::class);
        $openPgpKeyManager->expects(self::once())->method('deleteKey')->with('alice@example.org');

        $listener = new OpenPgpKeyListener($openPgpKeyManager, $userRepository, $aliasRepository);
        $listener->onUserDeleted(new UserEvent($user));
    }

    public function testOnUserDeletedKeepsKeyWhenAnotherAliasExists(): void
    {
        $user = new User('alice@example.org');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('existsByEmail')->willReturn(false);

        $otherAlias = new Alias();
        $otherAlias->setSource('alice@example.org');

        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findOneBySource')->willReturn($otherAlias);

        $openPgpKeyManager = $this->createMock(OpenPgpKeyManager::class);
        $openPgpKeyManager->expects(self::never())->method('deleteKey');

        $listener = new OpenPgpKeyListener($openPgpKeyManager, $userRepository, $aliasRepository);
        $listener->onUserDeleted(new UserEvent($user));
    }
}
