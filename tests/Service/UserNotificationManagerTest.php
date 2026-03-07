<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\PaginatedResult;
use App\Repository\UserNotificationRepository;
use App\Service\UserNotificationManager;
use PHPUnit\Framework\TestCase;

class UserNotificationManagerTest extends TestCase
{
    public function testFindPaginatedReturnsResult(): void
    {
        $repo = $this->createMock(UserNotificationRepository::class);
        $repo->expects($this->once())
            ->method('countByFilters')
            ->with('', '')
            ->willReturn(2);
        $repo->expects($this->once())
            ->method('findPaginatedByFilters')
            ->with('', '', 20, 0)
            ->willReturn(['item1', 'item2']);

        $manager = new UserNotificationManager($repo);
        $result = $manager->findPaginated(1);

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertSame(['item1', 'item2'], $result->items);
        self::assertSame(1, $result->page);
        self::assertSame(1, $result->totalPages);
        self::assertSame(2, $result->total);
    }

    public function testFindPaginatedPassesFilters(): void
    {
        $repo = $this->createMock(UserNotificationRepository::class);
        $repo->expects($this->once())
            ->method('countByFilters')
            ->with('user@example.org', 'password_compromised')
            ->willReturn(1);
        $repo->expects($this->once())
            ->method('findPaginatedByFilters')
            ->with('user@example.org', 'password_compromised', 20, 0)
            ->willReturn(['item1']);

        $manager = new UserNotificationManager($repo);
        $result = $manager->findPaginated(1, 'user@example.org', 'password_compromised');

        self::assertSame(['item1'], $result->items);
        self::assertSame(1, $result->total);
    }

    public function testFindPaginatedCalculatesOffset(): void
    {
        $repo = $this->createMock(UserNotificationRepository::class);
        $repo->expects($this->once())
            ->method('countByFilters')
            ->with('', '')
            ->willReturn(50);
        $repo->expects($this->once())
            ->method('findPaginatedByFilters')
            ->with('', '', 20, 20)
            ->willReturn([]);

        $manager = new UserNotificationManager($repo);
        $result = $manager->findPaginated(2);

        self::assertSame(2, $result->page);
        self::assertSame(3, $result->totalPages);
        self::assertSame(50, $result->total);
    }

    public function testFindPaginatedClampsPageToMinimumOne(): void
    {
        $repo = $this->createMock(UserNotificationRepository::class);
        $repo->expects($this->once())
            ->method('countByFilters')
            ->willReturn(0);
        $repo->expects($this->once())
            ->method('findPaginatedByFilters')
            ->with('', '', 20, 0)
            ->willReturn([]);

        $manager = new UserNotificationManager($repo);
        $result = $manager->findPaginated(-5);

        self::assertSame(1, $result->page);
        self::assertSame(1, $result->totalPages);
    }
}
