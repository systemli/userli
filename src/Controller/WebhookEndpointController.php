<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\WebhookEndpoint;
use App\Form\Model\WebhookEndpointModel;
use App\Form\WebhookEndpointType;
use App\Service\WebhookEndpointManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebhookEndpointController extends AbstractController
{
    public function __construct(private readonly WebhookEndpointManager $manager)
    {
    }

    #[Route('/settings/webhooks/', name: 'settings_webhook_endpoint_index', methods: ['GET'])]
    public function index(): Response
    {
        $endpoints = $this->manager->findAll();

        return $this->render('Settings/Webhook/Endpoint/index.html.twig', [
            'endpoints' => $endpoints,
        ]);
    }

    #[Route('/settings/webhooks/create', name: 'settings_webhook_endpoint_create', methods: ['GET'])]
    public function create(): Response
    {
        $form = $this->createForm(WebhookEndpointType::class, new WebhookEndpointModel(), [
            'action' => $this->generateUrl('settings_webhook_endpoint_create_post'),
            'method' => 'POST',
        ]);

        return $this->render('Settings/Webhook/Endpoint/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/webhooks/create', name: 'settings_webhook_endpoint_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $form = $this->createForm(WebhookEndpointType::class, new WebhookEndpointModel());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->manager->create($data->getUrl(), $data->getSecret(), $data->getEvents(), $data->isEnabled());
            $this->addFlash('success', 'settings.webhook.create.success');

            return $this->redirectToRoute('settings_webhook_endpoint_index');
        }

        return $this->render('Settings/Webhook/Endpoint/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/webhooks/edit/{id}', name: 'settings_webhook_endpoint_edit', methods: ['GET'])]
    public function edit(#[MapEntity] WebhookEndpoint $endpoint): Response
    {
        $model = new WebhookEndpointModel();
        $model->setUrl($endpoint->getUrl());
        $model->setSecret($endpoint->getSecret());
        $model->setEvents($endpoint->getEvents());
        $model->setEnabled($endpoint->isEnabled());

        $form = $this->createForm(WebhookEndpointType::class, $model, [
            'action' => $this->generateUrl('settings_webhook_endpoint_edit_post', ['id' => $endpoint->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('Settings/Webhook/Endpoint/form.html.twig', [
            'form' => $form,
            'endpoint' => $endpoint,
        ]);
    }

    #[Route('/settings/webhooks/edit/{id}', name: 'settings_webhook_endpoint_edit_post', methods: ['POST'])]
    public function editSubmit(#[MapEntity] WebhookEndpoint $endpoint, Request $request): Response
    {
        $form = $this->createForm(WebhookEndpointType::class, new WebhookEndpointModel());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->manager->update($endpoint, $data->getUrl(), $data->getSecret(), $data->getEvents(), $data->isEnabled());
            $this->addFlash('success', 'settings.webhook.edit.success');

            return $this->redirectToRoute('settings_webhook_endpoint_index');
        }

        return $this->render('Settings/Webhook/Endpoint/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/webhooks/delete/{id}', name: 'settings_webhook_endpoint_delete', methods: ['POST'])]
    public function delete(#[MapEntity] WebhookEndpoint $endpoint): RedirectResponse
    {
        $this->manager->delete($endpoint);
        $this->addFlash('success', 'settings.webhook.delete.success');

        return $this->redirectToRoute('settings_webhook_endpoint_index');
    }
}
