<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Model\Delete;
use App\Form\Model\PasswordChange;
use App\Form\PasswordChangeType;
use App\Form\UserDeleteType;
use App\Handler\DeleteHandler;
use App\Handler\MailCryptKeyHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    public function __construct(
        private readonly PasswordUpdater        $passwordUpdater,
        private readonly MailCryptKeyHandler    $mailCryptKeyHandler,
        private readonly EntityManagerInterface $manager,
        private readonly DeleteHandler          $deleteHandler
    )
    {
    }

    #[Route(path: '/account', name: 'account')]
    public function account(Request $request): Response
    {
        $user = $this->getUser();
        $passwordChange = new PasswordChange();
        $passwordChangeForm = $this->createForm(
            PasswordChangeType::class,
            $passwordChange,
            [
                'action' => $this->generateUrl('account'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $passwordChangeForm->handleRequest($request);

            if ($passwordChangeForm->isSubmitted() && $passwordChangeForm->isValid()) {
                $this->changePassword($request, $user, $passwordChange);
            }
        }

        return $this->render(
            'Start/account.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'password_form' => $passwordChangeForm->createView(),
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
                'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
            ]
        );
    }

    /**
     * @throws \Exception
     */
    private function changePassword(Request $request, User $user, PasswordChange $passwordChange): void
    {
        $this->passwordUpdater->updatePassword($user, $passwordChange->getNewPassword());
        // Reencrypt the MailCrypt key with new password
        if ($user->hasMailCryptSecretBox()) {
            $this->mailCryptKeyHandler->update($user, $passwordChange->getPassword(), $passwordChange->getNewPassword());
        }
        $user->eraseCredentials();

        $this->manager->flush();

        $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');
    }

    #[Route(path: '/user/delete', name: 'user_delete', methods: ['GET'])]
    public function delete(): RedirectResponse|Response
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
    public function deleteSubmit(Request $request): RedirectResponse|Response
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
