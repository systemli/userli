<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\SettingsType;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {
    }

    #[Route('/admin', name: 'admin_show', methods: ['GET'])]
    public function show(): Response
    {
        $form = $this->createForm(SettingsType::class);

        return $this->render('Admin/show.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin', name: 'admin_update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $form = $this->createForm(SettingsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->settingsService->setAll($form->getData());
            $this->addFlash('success', 'admin.flash.updated_successfully');

            return $this->redirectToRoute('admin_show');
        }

        return $this->render('Admin/show.html.twig', [
            'form' => $form,
        ]);
    }
}
