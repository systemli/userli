<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Message\SendAnnouncementMail;
use App\Repository\AnnouncementRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * Counts announcement mails as not sent once Messenger gives up retrying them.
 */
final readonly class AnnouncementMailFailedListener
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener]
    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if (!$message instanceof SendAnnouncementMail || $event->willRetry()) {
            return;
        }

        $this->logger->error('Announcement mail could not be sent', [
            'announcementId' => $message->announcementId,
            'userId' => $message->userId,
            'exception' => $event->getThrowable(),
        ]);

        $this->repository->incrementNotSentCount($message->announcementId);
    }
}
