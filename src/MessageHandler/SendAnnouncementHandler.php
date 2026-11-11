<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Announcement;
use App\Entity\Domain;
use App\Enum\AnnouncementStatus;
use App\Message\SendAnnouncement;
use App\Message\SendAnnouncementMail;
use App\Repository\UserRepository;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Fans out an announcement into one message per recipient.
 *
 * Messages are spread over time with a DelayStamp, so that at most
 * `announcement_mails_per_minute` mails become due per minute.
 *
 * Recipients are queued in batches. Each batch and the progress marker on the
 * announcement are written in one transaction, so a retried message resumes
 * after the last completed batch. With the Doctrine transport, this queues
 * every mail exactly once; with other transports, at most one batch may be
 * queued twice.
 */
#[AsMessageHandler(sign: true)]
final readonly class SendAnnouncementHandler
{
    private const int DEFAULT_MAILS_PER_MINUTE = 60;

    private const int BATCH_SIZE = 500;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private MessageBusInterface $bus,
        private SettingsService $settingsService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendAnnouncement $message): void
    {
        $announcement = $this->entityManager->getRepository(Announcement::class)->find($message->announcementId);

        if (null === $announcement) {
            $this->logger->warning('Announcement not found', ['announcementId' => $message->announcementId]);

            return;
        }

        if (AnnouncementStatus::DONE === $announcement->getStatus()) {
            $this->logger->warning('Announcement was already processed', ['announcementId' => $message->announcementId]);

            return;
        }

        $domain = $announcement->getDomain();
        if (null === $domain) {
            return;
        }

        // From now on, the recipient count holds the number of queued mails
        if (AnnouncementStatus::QUEUED === $announcement->getStatus()) {
            $announcement->setStatus(AnnouncementStatus::SENDING);
            $announcement->setRecipientCount(0);
            $this->entityManager->flush();
        }

        $mailsPerMinute = max(1, (int) $this->settingsService->get('announcement_mails_per_minute', self::DEFAULT_MAILS_PER_MINUTE));

        while ($this->queueNextBatch($announcement, $domain, $mailsPerMinute)) {
            // Continue until all recipients are queued
        }

        $announcement->setStatus(AnnouncementStatus::DONE);
        $this->entityManager->flush();

        $this->logger->info('Announcement queued', ['announcementId' => $message->announcementId, 'recipients' => $announcement->getRecipientCount()]);
    }

    /**
     * @return bool whether a batch was queued
     */
    private function queueNextBatch(Announcement $announcement, Domain $domain, int $mailsPerMinute): bool
    {
        $userIds = $this->userRepository->findAnnouncementRecipientIds($domain, $announcement->getLastQueuedUserId(), self::BATCH_SIZE);

        if ([] === $userIds) {
            return false;
        }

        $this->entityManager->wrapInTransaction(function () use ($announcement, $userIds, $mailsPerMinute): void {
            $count = $announcement->getRecipientCount();

            foreach ($userIds as $userId) {
                $delay = intdiv($count, $mailsPerMinute) * 60_000;
                $this->bus->dispatch(
                    new SendAnnouncementMail((int) $announcement->getId(), $userId),
                    $delay > 0 ? [new DelayStamp($delay)] : []
                );
                ++$count;
            }

            $announcement->setRecipientCount($count);
            $announcement->setLastQueuedUserId($userIds[array_key_last($userIds)]);

            $this->entityManager->flush();
        });

        return true;
    }
}
