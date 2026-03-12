<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\WebhookDelivery;
use App\Entity\WebhookEndpoint;
use App\Service\WebhookDeliveryManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebhookDeliveryController extends AbstractController
{
    public function __construct(private readonly WebhookDeliveryManager $manager)
    {
    }

    #[Route('/admin/webhooks/{id}/deliveries', name: 'admin_webhook_delivery_index', methods: ['GET'])]
    public function index(
        #[MapEntity] WebhookEndpoint $endpoint,
        Request $request,
    ): Response {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->getString('status', '');
        $eventType = $request->query->getString('eventType', '');
        $pagination = $this->manager->findPaginatedByEndpoint($endpoint, $page, $status, $eventType);

        return $this->render('Admin/Webhook/Delivery/index.html.twig', [
            'deliveries' => $pagination->items,
            'endpoint' => $endpoint,
            'page' => $pagination->page,
            'totalPages' => $pagination->totalPages,
            'total' => $pagination->total,
            'status' => $status,
            'eventType' => $eventType,
        ]);
    }

    #[Route('/admin/webhooks/deliveries/{id}', name: 'admin_webhook_delivery_show', methods: ['GET'])]
    public function show(#[MapEntity] WebhookDelivery $delivery): Response
    {
        return $this->render('Admin/Webhook/Delivery/show.html.twig', [
            'delivery' => $delivery,
            'endpoint' => $delivery->getEndpoint(),
        ]);
    }

    #[Route('/admin/webhooks/deliveries/{id}/retry', name: 'admin_webhook_delivery_retry', methods: ['POST'])]
    public function retry(#[MapEntity] WebhookDelivery $delivery, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('retry_delivery_'.$delivery->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_webhook_delivery_show', ['id' => $delivery->getId()]);
        }

        $this->manager->retry($delivery);
        $this->addFlash('success', 'admin.webhook.deliveries.retry.queued');

        return $this->redirectToRoute('admin_webhook_delivery_show', ['id' => $delivery->getId()]);
    }
}
