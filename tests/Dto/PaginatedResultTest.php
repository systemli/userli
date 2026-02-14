<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\PaginatedResult;
use App\Repository\SearchableRepositoryInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

class PaginatedResultTest extends TestCase
{
    public function testFromSearchableRepositoryDefaults(): void
    {
        $items = [new stdClass(), new stdClass()];

        $repository = $this->createMock(SearchableRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('countBySearch')
            ->with('')
            ->willReturn(2);
        $repository
            ->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('', 20, 0)
            ->willReturn($items);

        $result = PaginatedResult::fromSearchableRepository($repository, 1, 20);

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertSame($items, $result->items);
        self::assertEquals(1, $result->page);
        self::assertEquals(1, $result->totalPages);
        self::assertEquals(2, $result->total);
    }

    public function testFromSearchableRepositoryWithSearch(): void
    {
        $repository = $this->createMock(SearchableRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('countBySearch')
            ->with('example')
            ->willReturn(1);
        $repository
            ->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('example', 20, 0)
            ->willReturn([new stdClass()]);

        $result = PaginatedResult::fromSearchableRepository($repository, 1, 20, 'example');

        self::assertCount(1, $result->items);
        self::assertEquals(1, $result->total);
    }

    public function testFromSearchableRepositoryWithMultiplePages(): void
    {
        $repository = $this->createMock(SearchableRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('countBySearch')
            ->with('')
            ->willReturn(45);
        $repository
            ->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('', 20, 20)
            ->willReturn([new stdClass()]);

        $result = PaginatedResult::fromSearchableRepository($repository, 2, 20);

        self::assertEquals(2, $result->page);
        self::assertEquals(3, $result->totalPages);
        self::assertEquals(45, $result->total);
    }

    public function testFromSearchableRepositoryNegativePageClampedToOne(): void
    {
        $repository = $this->createMock(SearchableRepositoryInterface::class);
        $repository->method('countBySearch')->willReturn(5);
        $repository
            ->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('', 20, 0);

        $result = PaginatedResult::fromSearchableRepository($repository, -1, 20);

        self::assertEquals(1, $result->page);
    }

    public function testFromSearchableRepositoryZeroTotalReturnsOneTotalPage(): void
    {
        $repository = $this->createMock(SearchableRepositoryInterface::class);
        $repository->method('countBySearch')->willReturn(0);
        $repository->method('findPaginatedBySearch')->willReturn([]);

        $result = PaginatedResult::fromSearchableRepository($repository, 1, 20);

        self::assertEquals(1, $result->totalPages);
        self::assertEquals(0, $result->total);
        self::assertEmpty($result->items);
    }
}
