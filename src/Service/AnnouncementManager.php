<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\Announcement;
use App\Entity\Domain;
use App\Entity\User;
use App\Message\SendAnnouncement;
use App\Repository\AnnouncementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class AnnouncementManager
{
    private const int PAGE_SIZE = 20;

    public function __construct(
        private AnnouncementRepository $repository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * @return PaginatedResult<Announcement>
     */
    public function findPaginated(int $page = 1, ?Domain $domain = null): PaginatedResult
    {
        return PaginatedResult::paginate(
            $page,
            self::PAGE_SIZE,
            fn (): int => $this->repository->countByDomain($domain),
            fn (int $limit, int $offset): array => $this->repository->findPaginatedByDomain($domain, $limit, $offset),
        );
    }

    public function countRecipients(Domain $domain): int
    {
        return $this->userRepository->countAnnouncementRecipients($domain);
    }

    /**
     * Persists the announcement and queues the mails to all recipients of the domain.
     */
    public function send(Domain $domain, string $subject, string $body, ?User $author): Announcement
    {
        $announcement = new Announcement($domain, $subject, $body, $author, $this->countRecipients($domain));

        $this->entityManager->persist($announcement);
        $this->entityManager->flush();

        $this->bus->dispatch(new SendAnnouncement((int) $announcement->getId()));

        return $announcement;
    }
}
