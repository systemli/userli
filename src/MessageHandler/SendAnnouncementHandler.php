<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Announcement;
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
 */
#[AsMessageHandler(sign: true)]
final readonly class SendAnnouncementHandler
{
    private const int DEFAULT_MAILS_PER_MINUTE = 60;

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

        // Prevent sending mails twice when this message is retried after a partial run
        if (AnnouncementStatus::QUEUED !== $announcement->getStatus()) {
            $this->logger->warning('Announcement was already processed', ['announcementId' => $message->announcementId]);

            return;
        }

        $domain = $announcement->getDomain();
        if (null === $domain) {
            return;
        }

        $announcement->setStatus(AnnouncementStatus::SENDING);
        $this->entityManager->flush();

        $mailsPerMinute = max(1, (int) $this->settingsService->get('announcement_mails_per_minute', self::DEFAULT_MAILS_PER_MINUTE));
        $count = 0;

        foreach ($this->userRepository->iterateAnnouncementRecipientIds($domain) as $userId) {
            $delay = intdiv($count, $mailsPerMinute) * 60_000;
            $this->bus->dispatch(
                new SendAnnouncementMail($message->announcementId, $userId),
                $delay > 0 ? [new DelayStamp($delay)] : []
            );
            ++$count;
        }

        $announcement->setRecipientCount($count);
        $announcement->setStatus(AnnouncementStatus::DONE);

        $this->entityManager->flush();

        $this->logger->info('Announcement queued', ['announcementId' => $message->announcementId, 'recipients' => $count]);
    }
}
