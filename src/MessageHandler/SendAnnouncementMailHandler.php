<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Enum\Roles;
use App\Mail\AnnouncementMailer;
use App\Message\SendAnnouncementMail;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class SendAnnouncementMailHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AnnouncementRepository $announcementRepository,
        private AnnouncementMailer $mailer,
    ) {
    }

    public function __invoke(SendAnnouncementMail $message): void
    {
        $announcement = $this->announcementRepository->find($message->announcementId);

        if (null === $announcement) {
            return;
        }

        $user = $this->entityManager->getRepository(User::class)->find($message->userId);
        $domain = $announcement->getDomain();

        // The user may have been removed, deleted, flagged as spam or moved since the mail was queued
        if (null === $user || null === $domain || $user->isDeleted() || $user->hasRole(Roles::SPAM) || $user->getDomain()?->getId() !== $domain->getId()) {
            $this->announcementRepository->incrementNotSentCount($message->announcementId);

            return;
        }

        $this->mailer->send($user->getEmail(), $announcement->getSubject(), $announcement->getBody());
        $this->announcementRepository->incrementSentCount($message->announcementId);
    }
}
