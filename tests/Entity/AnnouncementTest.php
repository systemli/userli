<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Announcement;
use App\Entity\Domain;
use App\Enum\AnnouncementStatus;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class AnnouncementTest extends TestCase
{
    public function testPendingCountAndDelivered(): void
    {
        $announcement = new Announcement(new Domain(), 'Subject', 'Body', null, 5, 'token');
        $announcement->setStatus(AnnouncementStatus::DONE);
        $this->setCounters($announcement, 3, 1);

        self::assertSame(1, $announcement->getPendingCount());
        self::assertFalse($announcement->isDelivered());

        $this->setCounters($announcement, 3, 2);

        self::assertSame(0, $announcement->getPendingCount());
        self::assertTrue($announcement->isDelivered());
    }

    public function testNotDeliveredWhileRecipientsAreQueued(): void
    {
        $announcement = new Announcement(new Domain(), 'Subject', 'Body', null, 2, 'token');
        $announcement->setStatus(AnnouncementStatus::SENDING);
        $this->setCounters($announcement, 2, 0);

        self::assertSame(0, $announcement->getPendingCount());
        self::assertFalse($announcement->isDelivered());
    }

    private function setCounters(Announcement $announcement, int $sent, int $notSent): void
    {
        new ReflectionProperty(Announcement::class, 'sentCount')->setValue($announcement, $sent);
        new ReflectionProperty(Announcement::class, 'notSentCount')->setValue($announcement, $notSent);
    }
}
