<?php

declare(strict_types=1);

namespace App\Controller;

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

    #[Route('/settings/webhooks/{id}/deliveries', name: 'settings_webhook_delivery_index', methods: ['GET'])]
    public function index(
        #[MapEntity] WebhookEndpoint $endpoint,
    ): Response {
        $deliveries = $this->manager->findAllByEndpoint($endpoint);

        return $this->render('Settings/Webhook/Delivery/index.html.twig', [
            'deliveries' => $deliveries,
            'endpoint' => $endpoint,
        ]);
    }

    #[Route('/settings/webhooks/deliveries/{id}', name: 'settings_webhook_delivery_show', methods: ['GET'])]
    public function show(#[MapEntity] WebhookDelivery $delivery): Response
    {
        return $this->render('Settings/Webhook/Delivery/show.html.twig', [
            'delivery' => $delivery,
            'endpoint' => $delivery->getEndpoint(),
        ]);
    }

    #[Route('/settings/webhooks/deliveries/{id}/retry', name: 'settings_webhook_delivery_retry', methods: ['POST'])]
    public function retry(#[MapEntity] WebhookDelivery $delivery, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('retry_delivery_'.$delivery->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('settings_webhook_delivery_show', ['id' => $delivery->getId()]);
        }

        $this->manager->retry($delivery);
        $this->addFlash('success', 'settings.webhook.deliveries.retry.queued');

        return $this->redirectToRoute('settings_webhook_delivery_show', ['id' => $delivery->getId()]);
    }
}
