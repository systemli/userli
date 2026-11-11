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
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class SendAnnouncementHandlerTest extends TestCase
{
    public function testInvokeQueuesThrottledMailPerRecipient(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');
        $announcement = new Announcement($domain, 'Subject', 'Body', null, 0);

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('iterateAnnouncementRecipientIds')->willReturn([11, 12, 13, 14, 15]);

        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(5))
            ->method('dispatch')
            ->willReturnCallback(static function (object $message, array $stamps) use (&$dispatched): Envelope {
                $dispatched[] = [$message, $stamps];

                return new Envelope($message, $stamps);
            });

        $this->createHandler($announcement, $userRepository, $bus, 2)(new SendAnnouncement(7));

        $delays = array_map(
            static fn (array $entry): int => isset($entry[1][0]) && $entry[1][0] instanceof DelayStamp ? $entry[1][0]->getDelay() : 0,
            $dispatched
        );
        self::assertSame([0, 0, 60_000, 60_000, 120_000], $delays);

        $userIds = array_map(static function (array $entry): int {
            self::assertInstanceOf(SendAnnouncementMail::class, $entry[0]);
            self::assertSame(7, $entry[0]->announcementId);

            return $entry[0]->userId;
        }, $dispatched);
        self::assertSame([11, 12, 13, 14, 15], $userIds);

        self::assertSame(5, $announcement->getRecipientCount());
        self::assertSame(AnnouncementStatus::DONE, $announcement->getStatus());
    }

    public function testInvokeSkipsAlreadyProcessedAnnouncement(): void
    {
        $announcement = new Announcement(new Domain(), 'Subject', 'Body', null, 3);
        $announcement->setStatus(AnnouncementStatus::SENDING);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())->method('iterateAnnouncementRecipientIds');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->createHandler($announcement, $userRepository, $bus, 60)(new SendAnnouncement(7));

        self::assertSame(AnnouncementStatus::SENDING, $announcement->getStatus());
    }

    public function testInvokeIgnoresMissingAnnouncement(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->createHandler(null, $this->createStub(UserRepository::class), $bus, 60)(new SendAnnouncement(7));
    }

    private function createHandler(?Announcement $announcement, UserRepository $userRepository, MessageBusInterface $bus, int $mailsPerMinute): SendAnnouncementHandler
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($announcement);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $settings = $this->createStub(SettingsService::class);
        $settings->method('get')->willReturn($mailsPerMinute);

        return new SendAnnouncementHandler($em, $userRepository, $bus, $settings, $this->createStub(LoggerInterface::class));
    }
}
