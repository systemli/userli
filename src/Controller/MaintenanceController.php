<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\PruneUserNotifications;
use App\Message\PruneWebhookDeliveries;
use App\Message\UnlinkRedeemedVouchers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class MaintenanceController extends AbstractController
{
    private const TASKS = [
        'prune_user_notifications' => PruneUserNotifications::class,
        'prune_webhook_deliveries' => PruneWebhookDeliveries::class,
        'unlink_redeemed_vouchers' => UnlinkRedeemedVouchers::class,
    ];

    public function __construct(private readonly MessageBusInterface $bus)
    {
    }

    #[Route('/settings/maintenance', name: 'settings_maintenance_show', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('Settings/Maintenance/show.html.twig');
    }

    #[Route('/settings/maintenance/run/{task}', name: 'settings_maintenance_run', methods: ['POST'])]
    public function run(string $task): RedirectResponse
    {
        $messageClass = self::TASKS[$task] ?? null;
        if (!$messageClass) {
            $this->addFlash('error', 'settings.maintenance.unknown_task');

            return $this->redirectToRoute('settings_maintenance_show');
        }

        $this->bus->dispatch(new $messageClass());
        $this->addFlash('success', 'settings.maintenance.dispatched');

        return $this->redirectToRoute('settings_maintenance_show');
    }
}
