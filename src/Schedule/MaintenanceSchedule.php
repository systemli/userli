<?php

declare(strict_types=1);

namespace App\Schedule;

use App\Message\PruneUserNotifications;
use App\Message\PruneWebhookDeliveries;
use App\Message\RemoveInactiveUsers;
use App\Message\UnlinkRedeemedVouchers;
use DateTimeImmutable;
use Override;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('maintenance')]
final readonly class MaintenanceSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    #[Override]
    public function getSchedule(): Schedule
    {
        return new Schedule()
            ->stateful($this->cache) // ensure missed tasks are executed
            ->add(RecurringMessage::every('1 day', new PruneWebhookDeliveries(), new DateTimeImmutable('03:00')))
            ->add(RecurringMessage::every('1 day', new PruneUserNotifications(), new DateTimeImmutable('03:30')))
            ->add(RecurringMessage::every('1 day', new UnlinkRedeemedVouchers(), new DateTimeImmutable('04:00')))
            ->add(RecurringMessage::every('1 week', new RemoveInactiveUsers(), new DateTimeImmutable('06:00')));
    }
}
