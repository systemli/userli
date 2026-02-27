<?php

declare(strict_types=1);

namespace App\Repository;

interface SearchableRepositoryInterface
{
    public function countBySearch(string $search = ''): int;

    /**
     * @return object[]
     */
    public function findPaginatedBySearch(string $search, int $limit, int $offset): array;
}
