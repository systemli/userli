<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\Roles;
use App\Mail\AnnouncementMailer;
use App\Message\SendAnnouncementMail;
use App\Repository\AnnouncementRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler(sign: true)]
final readonly class SendAnnouncementMailHandler
{
    public function __construct(
        private AnnouncementRepository $announcementRepository,
        private UserRepository $userRepository,
        private AnnouncementMailer $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendAnnouncementMail $message): void
    {
        $announcement = $this->announcementRepository->find($message->announcementId);

        if (null === $announcement) {
            return;
        }

        $user = $this->userRepository->find($message->userId);
        $domain = $announcement->getDomain();

        // The user may have been removed, deleted, flagged as spam or moved since the mail was queued
        if (null === $user || null === $domain || $user->isDeleted() || $user->hasRole(Roles::SPAM) || $user->getDomain()?->getId() !== $domain->getId()) {
            $this->announcementRepository->incrementNotSentCount($message->announcementId);

            return;
        }

        $this->mailer->send($user->getEmail(), $announcement->getSubject(), $announcement->getBody());

        // The mail is out. Failing here would retry the message and send it twice,
        // so a lost counter update is only logged.
        try {
            $this->announcementRepository->incrementSentCount($message->announcementId);
        } catch (Throwable $throwable) {
            $this->logger->error('Could not count sent announcement mail', [
                'announcementId' => $message->announcementId,
                'userId' => $message->userId,
                'exception' => $throwable,
            ]);
        }
    }
}
