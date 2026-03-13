<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Roles;
use App\Form\Model\UserAdminModel;
use App\Form\UserAdminType;
use App\Service\UserManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserManager $manager,
    ) {
    }

    #[Route('/admin/users/', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');
        $deleted = $request->query->getString('deleted', 'active');
        $role = $request->query->getString('role', '');
        $mailCrypt = $request->query->getString('mailCrypt', '');
        $twofactor = $request->query->getString('twofactor', '');

        return $this->render('Admin/User/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search, $deleted, $role, $mailCrypt, $twofactor),
            'search' => $search,
            'deleted' => $deleted,
            'role' => $role,
            'mailCrypt' => $mailCrypt,
            'twofactor' => $twofactor,
            'all_roles' => Roles::getAll(),
        ]);
    }

    #[Route('/admin/users/create', name: 'admin_user_create', methods: ['GET'])]
    public function create(): Response
    {
        $model = new UserAdminModel();
        $model->setRoles([Roles::USER]);
        $model->setPasswordChangeRequired(true);

        $form = $this->createForm(UserAdminType::class, $model, [
            'action' => $this->generateUrl('admin_user_create_post'),
            'method' => 'POST',
        ]);

        return $this->render('Admin/User/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/users/create', name: 'admin_user_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $model = new UserAdminModel();
        $model->setRoles([Roles::USER]);
        $model->setPasswordChangeRequired(true);

        $form = $this->createForm(UserAdminType::class, $model, [
            'validation_groups' => ['Default', 'create'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGrantedForRoles($model->getRoles());

            $this->manager->create($model, $this->getAllowedRoles());
            $this->addFlash('success', 'admin.user.create.success');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('Admin/User/form.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/users/edit/{id}', name: 'admin_user_edit', methods: ['GET'])]
    public function edit(#[MapEntity] User $user): Response
    {
        if ($user->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGrantedForAdminUser($user);

        $model = UserAdminModel::fromUser($user);

        $form = $this->createForm(UserAdminType::class, $model, [
            'action' => $this->generateUrl('admin_user_edit_post', ['id' => $user->getId()]),
            'method' => 'POST',
            'is_edit' => true,
            'has_mail_crypt' => $user->hasMailCryptSecretBox(),
            'totp_enabled' => $user->isTotpAuthenticationEnabled(),
        ]);

        return $this->render('Admin/User/form.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/edit/{id}', name: 'admin_user_edit_post', methods: ['POST'])]
    public function editSubmit(#[MapEntity] User $user, Request $request): Response
    {
        if ($user->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGrantedForAdminUser($user);

        $model = new UserAdminModel();
        $form = $this->createForm(UserAdminType::class, $model, [
            'is_edit' => true,
            'has_mail_crypt' => $user->hasMailCryptSecretBox(),
            'totp_enabled' => $user->isTotpAuthenticationEnabled(),
            'validation_groups' => ['Default', 'edit'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGrantedForRoles($model->getRoles());

            $recoveryToken = $this->manager->update($user, $model, $this->getAllowedRoles());
            $this->addFlash('success', 'admin.user.edit.success');

            if (null !== $recoveryToken) {
                $this->addFlash('info', sprintf('Recovery Token: %s', $recoveryToken));
            }

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('Admin/User/form.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/delete/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(#[MapEntity] User $user, Request $request): RedirectResponse
    {
        if ($user->isDeleted()) {
            return $this->redirectToRoute('admin_user_index');
        }

        if (!$this->isCsrfTokenValid('delete_user_'.$user->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_user_index');
        }

        $this->denyAccessUnlessGrantedForAdminUser($user);

        $this->manager->delete($user);
        $this->addFlash('success', 'admin.user.delete.success');

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Deny access if the current user is not a full admin and the target user has admin role.
     */
    private function denyAccessUnlessGrantedForAdminUser(User $user): void
    {
        if ($user->hasRole(Roles::ADMIN) && !$this->isGranted(Roles::ADMIN)) {
            throw $this->createAccessDeniedException('Domain admins cannot manage admin users.');
        }
    }

    /**
     * Deny access if the submitted roles contain any role not reachable by the current user.
     *
     * @param string[] $roles
     */
    private function denyAccessUnlessGrantedForRoles(array $roles): void
    {
        $allowedRoles = $this->getAllowedRoles();
        foreach ($roles as $role) {
            if (!in_array($role, $allowedRoles, true)) {
                throw $this->createAccessDeniedException(sprintf('You are not allowed to assign the role "%s".', $role));
            }
        }
    }

    /**
     * @return string[]
     */
    private function getAllowedRoles(): array
    {
        $highestRole = $this->isGranted(Roles::ADMIN) ? [Roles::ADMIN] : [Roles::DOMAIN_ADMIN];

        return Roles::getReachableRoles($highestRole);
    }
}
