<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\UserNotification;
use App\Repository\UserNotificationRepository;

final readonly class UserNotificationManager
{
    private const int PAGE_SIZE = 20;

    public function __construct(
        private UserNotificationRepository $repository,
    ) {
    }

    /**
     * @return PaginatedResult<UserNotification>
     */
    public function findPaginated(int $page = 1, string $email = '', string $type = ''): PaginatedResult
    {
        return PaginatedResult::paginate(
            $page,
            self::PAGE_SIZE,
            fn (): int => $this->repository->countByFilters($email, $type),
            fn (int $limit, int $offset): array => $this->repository->findPaginatedByFilters($email, $type, $limit, $offset),
        );
    }
}
