<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Announcement;
use App\Entity\Domain;
use App\Enum\AnnouncementStatus;
use App\Message\SendAnnouncement;
use App\Message\SendAnnouncementMail;
use App\MessageHandler\SendAnnouncementHandler;
use App\Repository\UserRepository;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class SendAnnouncementHandlerTest extends TestCase
{
    /** @var list<array{0: object, 1: array}> */
    private array $dispatched = [];

    public function testInvokeQueuesThrottledMailPerRecipient(): void
    {
        $announcement = $this->createAnnouncement();

        $this->createHandler($announcement, $this->recipients([11, 12, 13, 14, 15]), $this->recordingBus(), 2)(new SendAnnouncement(7));

        self::assertSame([0, 0, 60_000, 60_000, 120_000], $this->dispatchedDelays());
        self::assertSame([11, 12, 13, 14, 15], $this->dispatchedUserIds());
        self::assertSame(5, $announcement->getRecipientCount());
        self::assertSame(15, $announcement->getLastQueuedUserId());
        self::assertSame(AnnouncementStatus::DONE, $announcement->getStatus());
    }

    public function testInvokeResumesAfterLastQueuedUser(): void
    {
        $announcement = $this->createAnnouncement();
        $announcement->setStatus(AnnouncementStatus::SENDING);
        $announcement->setRecipientCount(2);
        $announcement->setLastQueuedUserId(12);

        $this->createHandler($announcement, $this->recipients([11, 12, 13, 14, 15]), $this->recordingBus(), 2)(new SendAnnouncement(7));

        self::assertSame([13, 14, 15], $this->dispatchedUserIds());
        self::assertSame([60_000, 60_000, 120_000], $this->dispatchedDelays());
        self::assertSame(5, $announcement->getRecipientCount());
        self::assertSame(AnnouncementStatus::DONE, $announcement->getStatus());
    }

    public function testInvokeKeepsProgressWhenBatchFails(): void
    {
        $announcement = $this->createAnnouncement();

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willThrowException(new RuntimeException('Transport unavailable'));

        try {
            $this->createHandler($announcement, $this->recipients([11, 12]), $bus, 60)(new SendAnnouncement(7));
            self::fail('Expected exception');
        } catch (RuntimeException) {
        }

        self::assertSame(0, $announcement->getLastQueuedUserId());
        self::assertSame(0, $announcement->getRecipientCount());
        self::assertSame(AnnouncementStatus::SENDING, $announcement->getStatus());
    }

    public function testInvokeSkipsDoneAnnouncement(): void
    {
        $announcement = $this->createAnnouncement();
        $announcement->setStatus(AnnouncementStatus::DONE);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('findAnnouncementRecipientIds');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->createHandler($announcement, $userRepository, $bus, 60)(new SendAnnouncement(7));
    }

    public function testInvokeIgnoresMissingAnnouncement(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->createHandler(null, $this->createStub(UserRepository::class), $bus, 60)(new SendAnnouncement(7));
    }

    private function createAnnouncement(): Announcement
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $announcement = new Announcement($domain, 'Subject', 'Body', null, 5);
        new ReflectionProperty(Announcement::class, 'id')->setValue($announcement, 7);

        return $announcement;
    }

    /**
     * @param list<int> $userIds
     */
    private function recipients(array $userIds): UserRepository
    {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('findAnnouncementRecipientIds')
            ->willReturnCallback(static fn (Domain $domain, int $afterUserId, int $limit): array => array_slice(
                array_values(array_filter($userIds, static fn (int $id): bool => $id > $afterUserId)),
                0,
                $limit
            ));

        return $repository;
    }

    private function recordingBus(): MessageBusInterface
    {
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $message, array $stamps): Envelope {
            $this->dispatched[] = [$message, $stamps];

            return new Envelope($message, $stamps);
        });

        return $bus;
    }

    /**
     * @return list<int>
     */
    private function dispatchedDelays(): array
    {
        return array_map(
            static fn (array $entry): int => isset($entry[1][0]) && $entry[1][0] instanceof DelayStamp ? $entry[1][0]->getDelay() : 0,
            $this->dispatched
        );
    }

    /**
     * @return list<int>
     */
    private function dispatchedUserIds(): array
    {
        return array_map(static function (array $entry): int {
            self::assertInstanceOf(SendAnnouncementMail::class, $entry[0]);
            self::assertSame(7, $entry[0]->announcementId);

            return $entry[0]->userId;
        }, $this->dispatched);
    }

    private function createHandler(?Announcement $announcement, UserRepository $userRepository, MessageBusInterface $bus, int $mailsPerMinute): SendAnnouncementHandler
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($announcement);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->method('wrapInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback($em));

        $settings = $this->createStub(SettingsService::class);
        $settings->method('get')->willReturn($mailsPerMinute);

        return new SendAnnouncementHandler($em, $userRepository, $bus, $settings, $this->createStub(LoggerInterface::class));
    }
}
