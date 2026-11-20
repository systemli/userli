<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Alias;
use App\Enum\Roles;
use App\Exception\AliasConversionException;
use App\Exception\ValidationException;
use App\Form\AliasAdminType;
use App\Form\AliasConvertType;
use App\Form\Model\AliasAdminModel;
use App\Form\Model\AliasConvert;
use App\Service\AliasManager;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AliasController extends AbstractController
{
    use DomainFilterTrait;

    public function __construct(
        private readonly AliasManager $manager,
        private readonly UserManager $userManager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/admin/aliases/', name: 'admin_alias_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');
        $deleted = $request->query->getString('deleted', 'active');
        [$domain, $domainId, $selectedDomainName] = $this->resolveDomainFilter($request, $this->em);

        return $this->render('Admin/Alias/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search, $domain, $deleted),
            'search' => $search,
            'deleted' => $deleted,
            'selectedDomain' => $domainId,
            'selectedDomainName' => $selectedDomainName,
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
            $this->denyAccessUnlessGranted('create', $model);

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

        $this->denyAccessUnlessGranted('view', $alias);

        $isAdmin = $this->isGranted(Roles::ADMIN);
        $model = AliasAdminModel::fromAlias($alias);

        try {
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
        } catch (EntityNotFoundException) {
            $this->addFlash('error', 'admin.alias.edit.error_cross_domain');

            return $this->redirectToRoute('admin_alias_index');
        }
    }

    #[Route('/admin/aliases/edit/{id}', name: 'admin_alias_edit_post', methods: ['POST'])]
    public function editSubmit(#[MapEntity] Alias $alias, Request $request): Response
    {
        if ($alias->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $alias);

        $isAdmin = $this->isGranted(Roles::ADMIN);
        $model = new AliasAdminModel();
        $form = $this->createForm(AliasAdminType::class, $model, [
            'is_admin' => $isAdmin,
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()) {
                $this->manager->update($alias, $model);
                $this->addFlash('success', 'admin.alias.edit.success');

                return $this->redirectToRoute('admin_alias_index');
            }

            return $this->render('Admin/Alias/form.html.twig', [
                'form' => $form,
                'alias' => $alias,
            ]);
        } catch (EntityNotFoundException) {
            $this->addFlash('error', 'admin.alias.edit.error_cross_domain');

            return $this->redirectToRoute('admin_alias_index');
        }
    }

    #[Route('/admin/aliases/convert/{id}', name: 'admin_alias_convert', methods: ['GET'])]
    public function convert(#[MapEntity] Alias $alias): Response
    {
        if ($alias->isDeleted()) {
            throw $this->createNotFoundException();
        }

        // The new user gets the alias address, so this covers creating the user too
        $this->denyAccessUnlessGranted('edit', $alias);

        try {
            $this->userManager->assertConvertible($alias);
        } catch (AliasConversionException $aliasConversionException) {
            $this->addFlash('error', $aliasConversionException->getReason());

            return $this->redirectToRoute('admin_alias_index');
        }

        $form = $this->createForm(AliasConvertType::class, new AliasConvert(), [
            'action' => $this->generateUrl('admin_alias_convert_post', ['id' => $alias->getId()]),
            'method' => 'POST',
        ]);

        return $this->renderConvertForm($form, $alias);
    }

    #[Route('/admin/aliases/convert/{id}', name: 'admin_alias_convert_post', methods: ['POST'])]
    public function convertSubmit(#[MapEntity] Alias $alias, Request $request): Response
    {
        if ($alias->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $alias);

        $model = new AliasConvert();
        $form = $this->createForm(AliasConvertType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->userManager->createFromAlias($alias, (string) $model->getPlainPassword());
            } catch (AliasConversionException $e) {
                $this->addFlash('error', $e->getReason());

                return $this->redirectToRoute('admin_alias_index');
            }

            $this->addFlash('success', 'admin.alias.convert.success');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        return $this->renderConvertForm($form, $alias);
    }

    #[Route('/admin/aliases/delete/{id}', name: 'admin_alias_delete', methods: ['POST'])]
    public function delete(#[MapEntity] Alias $alias, Request $request): RedirectResponse
    {
        if ($alias->isDeleted()) {
            return $this->redirectToRoute('admin_alias_index');
        }

        $this->denyAccessUnlessGranted('view', $alias);

        if (!$this->isCsrfTokenValid('delete_alias_'.$alias->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_alias_index');
        }

        $this->manager->delete($alias);
        $this->addFlash('success', 'admin.alias.delete.success');

        return $this->redirectToRoute('admin_alias_index');
    }

    private function renderConvertForm(FormInterface $form, Alias $alias): Response
    {
        return $this->render('Admin/Alias/convert.html.twig', [
            'form' => $form,
            'alias' => $alias,
            'owner_email' => $this->getOwnerEmail($alias),
        ]);
    }

    private function getOwnerEmail(Alias $alias): ?string
    {
        try {
            return $alias->getUser()?->getEmail();
        } catch (EntityNotFoundException) {
            // The DomainFilter hides owners in other domains from domain admins
            return null;
        }
    }
}
