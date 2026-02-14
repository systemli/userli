<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\Domain;
use App\Event\DomainCreatedEvent;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class DomainManager
{
    private const int PAGE_SIZE = 20;

    public function __construct(
        private EntityManagerInterface $em,
        private DomainRepository $repository,
        private UserRepository $userRepository,
        private AliasRepository $aliasRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Find domains with offset-based pagination and optional search.
     *
     * @return PaginatedResult<Domain>
     */
    public function findPaginated(int $page = 1, string $search = ''): PaginatedResult
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $total = $this->repository->countBySearch($search);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $items = $this->repository->findPaginatedBySearch($search, self::PAGE_SIZE, $offset);

        return new PaginatedResult($items, $page, $totalPages, $total);
    }

    public function create(string $name): Domain
    {
        $domain = new Domain();
        $domain->setName($name);

        $this->em->persist($domain);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new DomainCreatedEvent($domain), DomainCreatedEvent::NAME);

        return $domain;
    }

    /**
     * @return array{users: int, aliases: int, admins: int}
     */
    public function getDomainStats(Domain $domain): array
    {
        return [
            'users' => $this->userRepository->countDomainUsers($domain),
            'aliases' => $this->aliasRepository->countDomainAliases($domain),
            'admins' => $this->userRepository->countDomainAdmins($domain),
        ];
    }
}
