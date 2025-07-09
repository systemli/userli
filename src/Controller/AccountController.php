<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Model\Delete;
use App\Form\Model\PasswordChange;
use App\Form\PasswordChangeType;
use App\Form\UserDeleteType;
use App\Handler\DeleteHandler;
use App\Handler\UserPasswordUpdateHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordUpdateHandler $userPasswordUpdateHandler,
        private readonly DeleteHandler             $deleteHandler
    )
    {
    }

    #[Route(path: '/account', name: 'account', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(PasswordChangeType::class, new PasswordChange(), [
            'action' => $this->generateUrl('account_password'),
            'method' => 'post',
        ]);

        return $this->render(
            'Start/account.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'password_form' => $form->createView(),
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
                'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
            ]
        );
    }

    #[Route(path: '/account/password', name: 'account_password', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        $user = $this->getUser();
        $data = new PasswordChange();
        $form = $this->createForm(PasswordChangeType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userPasswordUpdateHandler->updatePassword($user, $data->getNewPassword(), $data->getPassword());

            $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');

            return $this->redirectToRoute('account');
        }

        return $this->render(
            'Start/account.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'password_form' => $form->createView(),
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
                'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
            ]
        );
    }

    #[Route(path: '/user/delete', name: 'user_delete', methods: ['GET'])]
    public function delete(): Response
    {
        $form = $this->createForm(UserDeleteType::class, new Delete());

        return $this->render(
            'User/delete.html.twig',
            [
                'form' => $form->createView(),
                'user' => $this->getUser(),
            ]
        );
    }

    #[Route(path: '/user/delete', name: 'user_delete_submit', methods: ['POST'])]
    public function deleteSubmit(Request $request): Response
    {
        $form = $this->createForm(UserDeleteType::class, new Delete());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->deleteHandler->deleteUser($this->getUser());

            return $this->redirect($this->generateUrl('logout'));
        }

        return $this->render(
            'User/delete.html.twig',
            [
                'form' => $form->createView(),
                'user' => $this->getUser(),
            ]
        );
    }
}
