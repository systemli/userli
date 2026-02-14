<?php

declare(strict_types=1);

namespace App\Dto;

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
}
