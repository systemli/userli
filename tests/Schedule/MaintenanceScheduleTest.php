<?php

declare(strict_types=1);

namespace App\Tests\Schedule;

use App\Message\PruneUserNotifications;
use App\Message\PruneWebhookDeliveries;
use App\Message\RemoveInactiveUsers;
use App\Message\SendWeeklyReport;
use App\Message\UnlinkRedeemedVouchers;
use App\Schedule\MaintenanceSchedule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Contracts\Cache\CacheInterface;

class MaintenanceScheduleTest extends TestCase
{
    public function testScheduleContainsAllExpectedMessages(): void
    {
        $cache = $this->createStub(CacheInterface::class);
        $schedule = new MaintenanceSchedule($cache)->getSchedule();
        $recurringMessages = $schedule->getRecurringMessages();

        $messageTypes = array_map(
            static fn (RecurringMessage $rm) => (string) $rm->getProvider(),
            $recurringMessages,
        );

        self::assertCount(5, $recurringMessages);
        self::assertContains(PruneWebhookDeliveries::class, $messageTypes);
        self::assertContains(PruneUserNotifications::class, $messageTypes);
        self::assertContains(UnlinkRedeemedVouchers::class, $messageTypes);
        self::assertContains(RemoveInactiveUsers::class, $messageTypes);
        self::assertContains(SendWeeklyReport::class, $messageTypes);
    }
}
