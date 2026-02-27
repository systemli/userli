<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Form\DomainType;
use App\Service\DomainManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DomainController extends AbstractController
{
    public function __construct(
        private readonly DomainManager $manager,
    ) {
    }

    #[Route('/settings/domains/', name: 'settings_domain_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');

        return $this->render('Settings/Domain/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search),
            'search' => $search,
        ]);
    }

    #[Route('/settings/domains/create', name: 'settings_domain_create', methods: ['GET'])]
    public function create(): Response
    {
        $form = $this->createForm(DomainType::class, new Domain(), [
            'action' => $this->generateUrl('settings_domain_create_post'),
            'method' => 'POST',
        ]);

        return $this->render('Settings/Domain/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/domains/create', name: 'settings_domain_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $domain = new Domain();
        $form = $this->createForm(DomainType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->manager->create((string) $domain->getName());
                $this->addFlash('success', 'settings.domain.create.success');

                return $this->redirectToRoute('settings_domain_index');
            } catch (ValidationException) {
                $this->addFlash('error', 'settings.domain.create.error');
            }
        }

        return $this->render('Settings/Domain/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/domains/{id}', name: 'settings_domain_show', methods: ['GET'])]
    public function show(#[MapEntity] Domain $domain): Response
    {
        $stats = $this->manager->getDomainStats($domain);

        return $this->render('Settings/Domain/show.html.twig', [
            'domain' => $domain,
            'stats' => $stats,
        ]);
    }

    #[Route('/settings/domains/delete/{id}', name: 'settings_domain_delete', methods: ['POST'])]
    public function delete(#[MapEntity] Domain $domain, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_domain_'.$domain->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('settings_domain_index');
        }

        $user = $this->getUser();
        if ($user instanceof User && $user->getDomain() === $domain) {
            $this->addFlash('error', 'settings.domain.delete.error.own_domain');

            return $this->redirectToRoute('settings_domain_index');
        }

        $this->manager->delete($domain);
        $this->addFlash('success', 'settings.domain.delete.success');

        return $this->redirectToRoute('settings_domain_index');
    }
}
