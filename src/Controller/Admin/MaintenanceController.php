<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Message\PruneUserNotifications;
use App\Message\PruneWebhookDeliveries;
use App\Message\RemoveInactiveUsers;
use App\Message\UnlinkRedeemedVouchers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class MaintenanceController extends AbstractController
{
    private const array TASKS = [
        'prune_user_notifications' => PruneUserNotifications::class,
        'prune_webhook_deliveries' => PruneWebhookDeliveries::class,
        'remove_inactive_users' => RemoveInactiveUsers::class,
        'unlink_redeemed_vouchers' => UnlinkRedeemedVouchers::class,
    ];

    public function __construct(private readonly MessageBusInterface $bus)
    {
    }

    #[Route('/admin/maintenance', name: 'admin_maintenance_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('Admin/Maintenance/show.html.twig');
    }

    #[Route('/admin/maintenance/run/{task}', name: 'admin_maintenance_run', methods: ['POST'])]
    public function run(string $task): RedirectResponse
    {
        $messageClass = self::TASKS[$task] ?? null;
        if (!$messageClass) {
            $this->addFlash('error', 'admin.maintenance.unknown_task');

            return $this->redirectToRoute('admin_maintenance_show');
        }

        $this->bus->dispatch(new $messageClass());
        $this->addFlash('success', 'admin.maintenance.dispatched');

        return $this->redirectToRoute('admin_maintenance_show');
    }
}
