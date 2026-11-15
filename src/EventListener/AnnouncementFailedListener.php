<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Message\SendAnnouncement;
use App\Message\SendAnnouncementMail;
use App\Repository\AnnouncementRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * Records announcement messages that Messenger gives up retrying.
 *
 * A failed fan-out marks the announcement as failed, a failed mail counts as not sent.
 */
final readonly class AnnouncementFailedListener
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener]
    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof SendAnnouncement) {
            $this->logger->error('Announcement could not be queued', [
                'announcementId' => $message->announcementId,
                'exception' => $event->getThrowable(),
            ]);

            $this->repository->markFailed($message->announcementId);

            return;
        }

        if ($message instanceof SendAnnouncementMail) {
            $this->logger->error('Announcement mail could not be sent', [
                'announcementId' => $message->announcementId,
                'userId' => $message->userId,
                'exception' => $event->getThrowable(),
            ]);

            $this->repository->incrementNotSentCount($message->announcementId);
        }
    }
}
