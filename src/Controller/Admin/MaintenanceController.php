<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\Roles;
use App\Message\PruneUserNotifications;
use App\Message\PruneWebhookDeliveries;
use App\Message\RemoveInactiveUsers;
use App\Message\UnlinkRedeemedVouchers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Roles::ADMIN)]
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
    public function run(string $task, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('maintenance_'.$task, $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_maintenance_show');
        }

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
