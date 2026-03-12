<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\WebhookEndpoint;
use App\Enum\Roles;
use App\Form\Model\WebhookEndpointModel;
use App\Form\WebhookEndpointType;
use App\Service\WebhookEndpointManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Roles::ADMIN)]
final class WebhookEndpointController extends AbstractController
{
    public function __construct(private readonly WebhookEndpointManager $manager)
    {
    }

    #[Route('/admin/webhooks/', name: 'admin_webhook_endpoint_index', methods: ['GET'])]
    public function index(): Response
    {
        $endpoints = $this->manager->findAll();

        return $this->render('Admin/Webhook/Endpoint/index.html.twig', [
            'endpoints' => $endpoints,
        ]);
    }

    #[Route('/admin/webhooks/create', name: 'admin_webhook_endpoint_create', methods: ['GET'])]
    public function create(): Response
    {
        $form = $this->createForm(WebhookEndpointType::class, new WebhookEndpointModel(), [
            'action' => $this->generateUrl('admin_webhook_endpoint_create_post'),
            'method' => 'POST',
        ]);

        return $this->render('Admin/Webhook/Endpoint/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/webhooks/create', name: 'admin_webhook_endpoint_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $form = $this->createForm(WebhookEndpointType::class, new WebhookEndpointModel());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->manager->create($data->getUrl(), $data->getSecret(), $data->getEvents(), $data->isEnabled(), $data->getDomains());
            $this->addFlash('success', 'admin.webhook.create.success');

            return $this->redirectToRoute('admin_webhook_endpoint_index');
        }

        return $this->render('Admin/Webhook/Endpoint/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/webhooks/edit/{id}', name: 'admin_webhook_endpoint_edit', methods: ['GET'])]
    public function edit(#[MapEntity] WebhookEndpoint $endpoint): Response
    {
        $model = new WebhookEndpointModel();
        $model->setUrl($endpoint->getUrl());
        $model->setSecret($endpoint->getSecret());
        $model->setEvents($endpoint->getEvents());
        $model->setEnabled($endpoint->isEnabled());
        $model->setDomains($endpoint->getDomains()->toArray());

        $form = $this->createForm(WebhookEndpointType::class, $model, [
            'action' => $this->generateUrl('admin_webhook_endpoint_edit_post', ['id' => $endpoint->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('Admin/Webhook/Endpoint/form.html.twig', [
            'form' => $form,
            'endpoint' => $endpoint,
        ]);
    }

    #[Route('/admin/webhooks/edit/{id}', name: 'admin_webhook_endpoint_edit_post', methods: ['POST'])]
    public function editSubmit(#[MapEntity] WebhookEndpoint $endpoint, Request $request): Response
    {
        $form = $this->createForm(WebhookEndpointType::class, new WebhookEndpointModel());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->manager->update($endpoint, $data->getUrl(), $data->getSecret(), $data->getEvents(), $data->isEnabled(), $data->getDomains());
            $this->addFlash('success', 'admin.webhook.edit.success');

            return $this->redirectToRoute('admin_webhook_endpoint_index');
        }

        return $this->render('Admin/Webhook/Endpoint/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/webhooks/delete/{id}', name: 'admin_webhook_endpoint_delete', methods: ['POST'])]
    public function delete(#[MapEntity] WebhookEndpoint $endpoint, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_webhook_endpoint_'.$endpoint->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_webhook_endpoint_index');
        }

        $this->manager->delete($endpoint);
        $this->addFlash('success', 'admin.webhook.delete.success');

        return $this->redirectToRoute('admin_webhook_endpoint_index');
    }
}
