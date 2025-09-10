<?php

namespace App\Schedule;

use App\Message\PruneUserNotifications;
use App\Message\PruneWebhookDeliveries;
use App\Message\UnlinkRedeemedVouchers;
use DateTimeImmutable;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('maintenance')]
final class MaintenanceSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::every('1 day', new PruneWebhookDeliveries(), new DateTimeImmutable('03:00')))
            ->add(RecurringMessage::every('1 day', new PruneUserNotifications(), new DateTimeImmutable('03:30')))
            ->add(RecurringMessage::every('1 day', new UnlinkRedeemedVouchers(), new DateTimeImmutable('04:00')));
    }
}
