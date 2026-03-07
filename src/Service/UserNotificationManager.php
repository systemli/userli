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
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $total = $this->repository->countByFilters($email, $type);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $items = $this->repository->findPaginatedByFilters($email, $type, self::PAGE_SIZE, $offset);

        return new PaginatedResult($items, $page, $totalPages, $total);
    }
}
