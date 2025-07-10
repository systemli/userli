<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Model\Delete;
use App\Form\Model\PasswordChange;
use App\Form\Model\RecoveryToken;
use App\Form\Model\RecoveryTokenAck;
use App\Form\PasswordChangeType;
use App\Form\RecoveryTokenAckType;
use App\Form\RecoveryTokenType;
use App\Form\UserDeleteType;
use App\Handler\DeleteHandler;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    public function __construct(
        private readonly PasswordUpdater        $passwordUpdater,
        private readonly MailCryptKeyHandler    $mailCryptKeyHandler,
        private readonly EntityManagerInterface $manager,
        private readonly DeleteHandler          $deleteHandler,
        private readonly RecoveryTokenHandler   $recoveryTokenHandler,
    )
    {
    }

    #[Route(path: '/account', name: 'account', methods: ['GET'])]
    public function show(): Response
    {
        /** @var User $user */
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
                'password_form' => $form,
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
                'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
            ]
        );
    }

    #[Route(path: '/account/password', name: 'account_password', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(PasswordChangeType::class, new PasswordChange());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->passwordUpdater->updatePassword($user, $form->getData()->getNewPassword());

            // Reencrypt the MailCrypt key with new password
            if ($user->hasMailCryptSecretBox()) {
                $this->mailCryptKeyHandler->update($user, $form->getData()->getPassword(), $form->getData()->getNewPassword());
            }

            $user->eraseCredentials();

            $this->manager->flush();

            $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');

            return $this->redirectToRoute('account');
        }

        return $this->render(
            'Start/account.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'password_form' => $form,
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
                'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
            ]
        );
    }

    #[Route(path: '/user/delete', name: 'user_delete', methods: ['GET'])]
    public function delete(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserDeleteType::class, new Delete());

        return $this->render(
            'User/delete.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/user/delete', name: 'user_delete_submit', methods: ['POST'])]
    public function deleteSubmit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserDeleteType::class, new Delete());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->deleteHandler->deleteUser($user);

            return $this->redirectToRoute('logout');
        }

        return $this->render(
            'User/delete.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/user/recovery_token', name: 'user_recovery_token', methods: ['GET'])]
    public function recoveryToken(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(RecoveryTokenType::class, new RecoveryToken(), [
            'action' => $this->generateUrl('user_recovery_token_submit'),
            'method' => 'post',
        ]);

        return $this->render('User/recovery_token.html.twig',
            [
                'form' => $form,
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/user/recovery_token', name: 'user_recovery_token_submit', methods: ['POST'])]
    public function recoveryTokenSubmit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = new RecoveryToken();
        $form = $this->createForm(RecoveryTokenType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if user has a MailCrypt key
            if ($user->hasMailCryptSecretBox()) {
                // Decrypt the MailCrypt key
                $user->setPlainMailCryptPrivateKey($this->mailCryptKeyHandler->decrypt($user, $data->password));
            } else {
                // Create a new MailCrypt key if none existed before
                $this->mailCryptKeyHandler->create($user, $data->password);
            }

            // Generate a new recovery token and encrypt the MailCrypt key with it
            $this->recoveryTokenHandler->create($user);
            if (null === $recoveryToken = $user->getPlainRecoveryToken()) {
                throw new Exception('recoveryToken should not be null');
            }

            // Clear sensitive plaintext data from User object
            $user->eraseCredentials();

            return $this->redirectToRoute('user_recovery_token_ack', ['recovery_token' => $recoveryToken]);
        }

        return $this->render('User/recovery_token.html.twig',
            [
                'form' => $form,
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/user/recovery_token/ack', name: 'user_recovery_token_ack', methods: ['GET'])]
    public function recoveryTokenAck(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(
            RecoveryTokenAckType::class,
            new RecoveryTokenAck(),
            [
                'action' => $this->generateUrl('user_recovery_token_ack_submit'),
                'method' => 'post',
            ]
        );

        return $this->render('User/recovery_token.html.twig',
            [
                'form' => $form,
                'recovery_token' => $request->query->get('recovery_token'),
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/user/recovery_token/ack', name: 'user_recovery_token_ack_submit', methods: ['POST'])]
    public function recoveryTokenAckSubmit(Request $request): Response
    {
        $data = new RecoveryTokenAck();
        $form = $this->createForm(RecoveryTokenAckType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid()) {
            $request->getSession()->getFlashBag()->add('success', 'flashes.recovery-token-ack');

            return $this->redirectToRoute('start');
        }

        return $this->render('User/recovery_token.html.twig',
            [
                'form' => $form,
                'recovery_token' => $data->getRecoveryToken(),
            ]
        );
    }
}
