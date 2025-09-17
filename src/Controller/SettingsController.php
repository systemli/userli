<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\SettingsType;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SettingsService $settingsService
    )
    {
    }

    #[Route('/settings', name: 'settings_show', methods: ['GET'])]
    public function show(): Response
    {
        $form = $this->createForm(SettingsType::class);

        return $this->render('Settings/show.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/settings', name: 'settings_update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $form = $this->createForm(SettingsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Filter out null values and save only changed settings
            $data = array_filter($data, fn($value) => $value !== null);

            $this->settingsService->setAll($data);
            $this->addFlash('success', 'settings.flash.updated_successfully');

            return $this->redirectToRoute('settings_show');
        }

        return $this->render('Settings/show.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
