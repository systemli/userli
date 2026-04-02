<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Form\DomainEditType;
use App\Form\DomainType;
use App\Form\Model\PasswordConfirmation;
use App\Form\PasswordConfirmationType;
use App\Service\DomainManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Roles::ADMIN)]
final class DomainController extends AbstractController
{
    public function __construct(
        private readonly DomainManager $manager,
    ) {
    }

    #[Route('/admin/domains/', name: 'admin_domain_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');

        return $this->render('Admin/Domain/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search),
            'search' => $search,
        ]);
    }

    #[Route('/admin/domains/create', name: 'admin_domain_create', methods: ['GET'])]
    public function create(): Response
    {
        $form = $this->createForm(DomainType::class, new Domain(), [
            'action' => $this->generateUrl('admin_domain_create_post'),
            'method' => 'POST',
        ]);

        return $this->render('Admin/Domain/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/domains/create', name: 'admin_domain_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $domain = new Domain();
        $form = $this->createForm(DomainType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->manager->create((string) $domain->getName());
                $this->addFlash('success', 'admin.domain.create.success');

                return $this->redirectToRoute('admin_domain_index');
            } catch (ValidationException) {
                $this->addFlash('error', 'admin.domain.create.error');
            }
        }

        return $this->render('Admin/Domain/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/domains/edit/{id}', name: 'admin_domain_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function edit(#[MapEntity] Domain $domain): Response
    {
        $form = $this->createForm(DomainEditType::class, $domain, [
            'action' => $this->generateUrl('admin_domain_edit_post', ['id' => $domain->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('Admin/Domain/edit.html.twig', [
            'domain' => $domain,
            'form' => $form,
        ]);
    }

    #[Route('/admin/domains/edit/{id}', name: 'admin_domain_edit_post', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editSubmit(#[MapEntity] Domain $domain, Request $request): Response
    {
        $form = $this->createForm(DomainEditType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->update($domain);
            $this->addFlash('success', 'admin.domain.edit.success');

            return $this->redirectToRoute('admin_domain_show', ['id' => $domain->getId()]);
        }

        return $this->render('Admin/Domain/edit.html.twig', [
            'domain' => $domain,
            'form' => $form,
        ]);
    }

    #[Route('/admin/domains/{id}', name: 'admin_domain_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(#[MapEntity] Domain $domain): Response
    {
        $stats = $this->manager->getDomainStats($domain);

        return $this->render('Admin/Domain/show.html.twig', [
            'domain' => $domain,
            'stats' => $stats,
        ]);
    }

    #[Route('/admin/domains/delete/{id}', name: 'admin_domain_delete_post', methods: ['POST'])]
    public function deleteSubmit(#[MapEntity] Domain $domain, Request $request): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $user->getDomain() === $domain) {
            $this->addFlash('error', 'admin.domain.delete.error.own_domain');

            return $this->redirectToRoute('admin_domain_index');
        }

        $form = $this->createForm(PasswordConfirmationType::class, new PasswordConfirmation(), [
            'submit_label' => 'delete.domain.submit',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->delete($domain);
            $this->addFlash('success', 'admin.domain.delete.success');

            return $this->redirectToRoute('admin_domain_index');
        }

        $this->addFlash('error', 'flashes.password-confirmation-failed');

        return $this->redirectToRoute('admin_domain_index');
    }
}
