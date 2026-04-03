<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\Domain;
use App\Event\DomainEvent;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
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
        private VoucherRepository $voucherRepository,
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
        return PaginatedResult::fromSearchableRepository($this->repository, $page, self::PAGE_SIZE, $search);
    }

    public function create(string $name): Domain
    {
        $domain = new Domain();
        $domain->setName($name);

        $this->em->persist($domain);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new DomainEvent($domain), DomainEvent::CREATED);

        return $domain;
    }

    public function delete(Domain $domain): void
    {
        $this->eventDispatcher->dispatch(new DomainEvent($domain), DomainEvent::DELETED);

        // The database CASCADE constraints handle deletion of all dependent entities
        $this->em->remove($domain);
        $this->em->flush();
    }

    /**
     * @return array{users: int, aliases: int, admins: int, vouchers: int}
     */
    public function getDomainStats(Domain $domain): array
    {
        return [
            'users' => $this->userRepository->countDomainUsers($domain),
            'aliases' => $this->aliasRepository->countDomainAliases($domain),
            'admins' => $this->userRepository->countDomainAdmins($domain),
            'vouchers' => $this->voucherRepository->countByDomain($domain),
        ];
    }
}
