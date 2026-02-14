<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ReservedName;
use App\Exception\ValidationException;
use App\Form\ReservedNameImportType;
use App\Form\ReservedNameType;
use App\Service\ReservedNameManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReservedNameController extends AbstractController
{
    public function __construct(
        private readonly ReservedNameManager $manager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/settings/reserved-names/', name: 'settings_reserved_name_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->getString('search', '');
        $pagination = $this->manager->findPaginated($page, $search);

        return $this->render('Settings/ReservedName/index.html.twig', [
            'reservedNames' => $pagination->items,
            'page' => $pagination->page,
            'totalPages' => $pagination->totalPages,
            'total' => $pagination->total,
            'search' => $search,
        ]);
    }

    #[Route('/settings/reserved-names/create', name: 'settings_reserved_name_create', methods: ['GET'])]
    public function create(): Response
    {
        $form = $this->createForm(ReservedNameType::class, new ReservedName(), [
            'action' => $this->generateUrl('settings_reserved_name_create_post'),
            'method' => 'POST',
        ]);

        return $this->render('Settings/ReservedName/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/reserved-names/create', name: 'settings_reserved_name_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $reservedName = new ReservedName();
        $form = $this->createForm(ReservedNameType::class, $reservedName);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->manager->create((string) $reservedName->getName());
                $this->addFlash('success', 'settings.reserved-name.create.success');

                return $this->redirectToRoute('settings_reserved_name_index');
            } catch (ValidationException) {
                $this->addFlash('error', 'settings.reserved-name.create.error');
            }
        }

        return $this->render('Settings/ReservedName/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/reserved-names/delete/{id}', name: 'settings_reserved_name_delete', methods: ['POST'])]
    public function delete(#[MapEntity] ReservedName $reservedName, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_reserved_name_'.$reservedName->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('settings_reserved_name_index');
        }

        $this->manager->delete($reservedName);
        $this->addFlash('success', 'settings.reserved-name.delete.success');

        return $this->redirectToRoute('settings_reserved_name_index');
    }

    #[Route('/settings/reserved-names/import', name: 'settings_reserved_name_import', methods: ['GET'])]
    public function import(): Response
    {
        $form = $this->createForm(ReservedNameImportType::class, null, [
            'action' => $this->generateUrl('settings_reserved_name_import_post'),
            'method' => 'POST',
        ]);

        return $this->render('Settings/ReservedName/import.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/reserved-names/import', name: 'settings_reserved_name_import_post', methods: ['POST'])]
    public function importSubmit(Request $request): Response
    {
        $form = $this->createForm(ReservedNameImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $result = $this->manager->importFromFile($file);

            $this->addFlash('success', $this->translator->trans(
                'settings.reserved-name.import.success',
                ['%imported%' => $result['imported'], '%skipped%' => $result['skipped']]
            ));

            return $this->redirectToRoute('settings_reserved_name_index');
        }

        return $this->render('Settings/ReservedName/import.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/reserved-names/export', name: 'settings_reserved_name_export', methods: ['GET'])]
    public function export(): Response
    {
        $content = $this->manager->exportAsText();

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'reserved_names.txt'
            )
        );

        return $response;
    }
}
