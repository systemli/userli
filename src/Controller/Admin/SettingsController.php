<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\Roles;
use App\Form\SettingsType;
use App\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Roles::ADMIN)]
final class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {
    }

    #[Route('/admin/settings', name: 'admin_settings', methods: ['GET'])]
    public function show(): Response
    {
        $form = $this->createForm(SettingsType::class);

        return $this->render('Admin/show.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/settings', name: 'admin_settings_update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $form = $this->createForm(SettingsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->settingsService->setAll($form->getData());
            $this->addFlash('success', 'admin.flash.updated_successfully');

            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('Admin/show.html.twig', [
            'form' => $form,
        ]);
    }
}
