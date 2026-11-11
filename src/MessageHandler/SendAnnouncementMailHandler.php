<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Announcement;
use App\Entity\User;
use App\Enum\Roles;
use App\Mail\AnnouncementMailer;
use App\Message\SendAnnouncementMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class SendAnnouncementMailHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AnnouncementMailer $mailer,
    ) {
    }

    public function __invoke(SendAnnouncementMail $message): void
    {
        $announcement = $this->entityManager->getRepository(Announcement::class)->find($message->announcementId);
        $user = $this->entityManager->getRepository(User::class)->find($message->userId);

        if (null === $announcement || null === $user) {
            return;
        }

        $domain = $announcement->getDomain();

        // The user may have been deleted, flagged as spam or moved since the mail was queued
        if (null === $domain || $user->isDeleted() || $user->hasRole(Roles::SPAM) || $user->getDomain()?->getId() !== $domain->getId()) {
            return;
        }

        $this->mailer->send($user->getEmail(), $announcement->getSubject(), $announcement->getBody());
    }
}
