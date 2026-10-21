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
        $repository = $this->createStub(SearchableRepositoryInterface::class);
        $repository->method('countBySearch')->willReturn(0);
        $repository->method('findPaginatedBySearch')->willReturn([]);

        $result = PaginatedResult::fromSearchableRepository($repository, 1, 20);

        self::assertEquals(1, $result->totalPages);
        self::assertEquals(0, $result->total);
        self::assertEmpty($result->items);
    }

    public function testPaginatePassesLimitAndOffset(): void
    {
        $items = [new stdClass()];
        $calls = [];

        $result = PaginatedResult::paginate(3, 20, static fn (): int => 45, static function (int $limit, int $offset) use (&$calls, $items): array {
            $calls[] = [$limit, $offset];

            return $items;
        });

        self::assertSame([[20, 40]], $calls);
        self::assertSame($items, $result->items);
        self::assertSame(3, $result->page);
        self::assertSame(3, $result->totalPages);
        self::assertSame(45, $result->total);
    }

    public function testPaginateClampsPageAndKeepsOneEmptyPage(): void
    {
        $result = PaginatedResult::paginate(0, 20, static fn (): int => 0, static fn (int $limit, int $offset): array => 0 === $offset ? [] : ['wrong offset']);

        self::assertSame([], $result->items);
        self::assertSame(1, $result->page);
        self::assertSame(1, $result->totalPages);
        self::assertSame(0, $result->total);
    }
}
