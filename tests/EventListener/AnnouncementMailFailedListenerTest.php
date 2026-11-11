<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\AnnouncementMailFailedListener;
use App\Message\SendAnnouncement;
use App\Message\SendAnnouncementMail;
use App\Repository\AnnouncementRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class AnnouncementMailFailedListenerTest extends TestCase
{
    public function testCountsMailAsNotSentWhenRetriesAreExhausted(): void
    {
        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->expects($this->once())->method('incrementNotSentCount')->with(3);

        $this->createListener($repository)->onMessageFailed($this->createEvent(new SendAnnouncementMail(3, 9), false));
    }

    public function testIgnoresMailThatWillBeRetried(): void
    {
        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->expects($this->never())->method('incrementNotSentCount');

        $this->createListener($repository)->onMessageFailed($this->createEvent(new SendAnnouncementMail(3, 9), true));
    }

    public function testIgnoresOtherMessages(): void
    {
        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->expects($this->never())->method('incrementNotSentCount');

        $this->createListener($repository)->onMessageFailed($this->createEvent(new SendAnnouncement(3), false));
    }

    private function createEvent(object $message, bool $willRetry): WorkerMessageFailedEvent
    {
        $event = new WorkerMessageFailedEvent(new Envelope($message), 'async', new RuntimeException('SMTP error'));

        if ($willRetry) {
            $event->setForRetry();
        }

        return $event;
    }

    private function createListener(AnnouncementRepository $repository): AnnouncementMailFailedListener
    {
        return new AnnouncementMailFailedListener($repository, $this->createStub(LoggerInterface::class));
    }
}
