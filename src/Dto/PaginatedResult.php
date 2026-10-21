<?php

declare(strict_types=1);

namespace App\Dto;

use App\Repository\SearchableRepositoryInterface;
use Closure;

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
        return self::paginate(
            $page,
            $pageSize,
            static fn (): int => $repository->countBySearch($search),
            static fn (int $limit, int $offset): array => $repository->findPaginatedBySearch($search, $limit, $offset),
        );
    }

    /**
     * @template TItem
     *
     * @param Closure(): int                            $count
     * @param Closure(int $limit, int $offset): TItem[] $find
     *
     * @return self<TItem>
     */
    public static function paginate(int $page, int $pageSize, Closure $count, Closure $find): self
    {
        $page = max(1, $page);
        $total = $count();
        $totalPages = max(1, (int) ceil($total / $pageSize));

        return new self($find($pageSize, ($page - 1) * $pageSize), $page, $totalPages, $total);
    }
}
