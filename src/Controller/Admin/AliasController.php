<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Alias;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Form\AliasAdminType;
use App\Form\Model\AliasAdminModel;
use App\Service\AliasManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AliasController extends AbstractController
{
    public function __construct(
        private readonly AliasManager $manager,
    ) {
    }

    #[Route('/admin/aliases/', name: 'admin_alias_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');
        $deleted = $request->query->getString('deleted', 'active');

        return $this->render('Admin/Alias/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search, $deleted),
            'search' => $search,
            'deleted' => $deleted,
        ]);
    }

    #[Route('/admin/aliases/create', name: 'admin_alias_create', methods: ['GET'])]
    public function create(): Response
    {
        $isAdmin = $this->isGranted(Roles::ADMIN);
        $form = $this->createForm(AliasAdminType::class, new AliasAdminModel(), [
            'action' => $this->generateUrl('admin_alias_create_post'),
            'method' => 'POST',
            'is_admin' => $isAdmin,
        ]);

        return $this->render('Admin/Alias/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/aliases/create', name: 'admin_alias_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $isAdmin = $this->isGranted(Roles::ADMIN);
        $model = new AliasAdminModel();
        $form = $this->createForm(AliasAdminType::class, $model, [
            'is_admin' => $isAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->manager->create($model);
                $this->addFlash('success', 'admin.alias.create.success');

                return $this->redirectToRoute('admin_alias_index');
            } catch (ValidationException) {
                $this->addFlash('error', 'admin.alias.create.error');
            }
        }

        return $this->render('Admin/Alias/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/aliases/edit/{id}', name: 'admin_alias_edit', methods: ['GET'])]
    public function edit(#[MapEntity] Alias $alias): Response
    {
        if ($alias->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $isAdmin = $this->isGranted(Roles::ADMIN);
        $model = AliasAdminModel::fromAlias($alias);

        $form = $this->createForm(AliasAdminType::class, $model, [
            'action' => $this->generateUrl('admin_alias_edit_post', ['id' => $alias->getId()]),
            'method' => 'POST',
            'is_admin' => $isAdmin,
            'is_edit' => true,
        ]);

        return $this->render('Admin/Alias/form.html.twig', [
            'form' => $form,
            'alias' => $alias,
        ]);
    }

    #[Route('/admin/aliases/edit/{id}', name: 'admin_alias_edit_post', methods: ['POST'])]
    public function editSubmit(#[MapEntity] Alias $alias, Request $request): Response
    {
        if ($alias->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $isAdmin = $this->isGranted(Roles::ADMIN);
        $model = new AliasAdminModel();
        $form = $this->createForm(AliasAdminType::class, $model, [
            'is_admin' => $isAdmin,
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->update($alias, $model);
            $this->addFlash('success', 'admin.alias.edit.success');

            return $this->redirectToRoute('admin_alias_index');
        }

        return $this->render('Admin/Alias/form.html.twig', [
            'form' => $form,
            'alias' => $alias,
        ]);
    }

    #[Route('/admin/aliases/delete/{id}', name: 'admin_alias_delete', methods: ['POST'])]
    public function delete(#[MapEntity] Alias $alias, Request $request): RedirectResponse
    {
        if ($alias->isDeleted()) {
            return $this->redirectToRoute('admin_alias_index');
        }

        if (!$this->isCsrfTokenValid('delete_alias_'.$alias->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_alias_index');
        }

        $this->manager->delete($alias);
        $this->addFlash('success', 'admin.alias.delete.success');

        return $this->redirectToRoute('admin_alias_index');
    }
}
