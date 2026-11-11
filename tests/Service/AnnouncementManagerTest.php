<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Announcement;
use App\Entity\Domain;
use App\Message\SendAnnouncement;
use App\Repository\AnnouncementRepository;
use App\Repository\UserRepository;
use App\Service\AnnouncementManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class AnnouncementManagerTest extends TestCase
{
    public function testSendPersistsAndQueuesAnnouncement(): void
    {
        $repository = $this->createStub(AnnouncementRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(SendAnnouncement::class))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $announcement = $this->createManager($repository, $entityManager, $bus)->send(new Domain(), 'Subject', 'Body', null, 'token');

        self::assertNotNull($announcement);
        self::assertSame('token', $announcement->getSendToken());
    }

    public function testSendMarksAnnouncementFailedWhenDispatchFails(): void
    {
        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $repository->expects($this->once())->method('markFailed');

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willThrowException(new RuntimeException('Transport down'));

        $this->expectException(RuntimeException::class);

        $this->createManager($repository, $this->createStub(EntityManagerInterface::class), $bus)->send(new Domain(), 'Subject', 'Body', null, 'token');
    }

    public function testSendIgnoresAlreadyUsedToken(): void
    {
        $repository = $this->createStub(AnnouncementRepository::class);
        $repository->method('findOneBy')->willReturn(new Announcement(new Domain(), 'Subject', 'Body', null, 1, 'token'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        self::assertNull($this->createManager($repository, $entityManager, $bus)->send(new Domain(), 'Subject', 'Body', null, 'token'));
    }

    public function testSendIgnoresTokenUsedByConcurrentRequest(): void
    {
        $repository = $this->createStub(AnnouncementRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('flush')->willThrowException($this->createStub(UniqueConstraintViolationException::class));

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        self::assertNull($this->createManager($repository, $entityManager, $bus)->send(new Domain(), 'Subject', 'Body', null, 'token'));
    }

    private function createManager(AnnouncementRepository $repository, EntityManagerInterface $entityManager, MessageBusInterface $bus): AnnouncementManager
    {
        return new AnnouncementManager($repository, $this->createStub(UserRepository::class), $entityManager, $bus);
    }
}
