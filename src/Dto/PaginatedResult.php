<?php

declare(strict_types=1);

namespace App\Dto;

use App\Repository\SearchableRepositoryInterface;

/**
 * @template T
 */
final readonly class PaginatedResult
{
    /**
     * @param T[] $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {
    }

    /**
     * @return self<object>
     */
    public static function fromSearchableRepository(
        SearchableRepositoryInterface $repository,
        int $page,
        int $pageSize,
        string $search = '',
    ): self {
        $page = max(1, $page);
        $offset = ($page - 1) * $pageSize;
        $total = $repository->countBySearch($search);
        $totalPages = max(1, (int) ceil($total / $pageSize));
        $items = $repository->findPaginatedBySearch($search, $pageSize, $offset);

        return new self($items, $page, $totalPages, $total);
    }
}
