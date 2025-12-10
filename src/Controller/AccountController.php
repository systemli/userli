<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Event\UserEvent;
use App\Form\Model\Delete;
use App\Form\Model\Password;
use App\Form\Model\RecoveryToken;
use App\Form\Model\RecoveryTokenConfirm;
use App\Form\PasswordType;
use App\Form\RecoveryTokenConfirmType;
use App\Form\RecoveryTokenType;
use App\Form\UserDeleteType;
use App\Handler\DeleteHandler;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Handler\WkdHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    public function __construct(
        private readonly PasswordUpdater $passwordUpdater,
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly EntityManagerInterface $manager,
        private readonly DeleteHandler $deleteHandler,
        private readonly RecoveryTokenHandler $recoveryTokenHandler,
        private readonly WkdHandler $wkdHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route(path: '/account', name: 'account', methods: ['GET'])]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $openPgpKey = $this->wkdHandler->getKey($user->getEmail());

        return $this->render(
            'Account/show.html.twig',
            [
                'user' => $user,
                'openpgp_key' => $openPgpKey,
            ]
        );
    }

    #[Route(path: '/account/password', name: 'account_password', methods: ['GET'])]
    public function passwordChangeForm(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(PasswordType::class, new Password(), [
            'action' => $this->generateUrl('account_password_submit'),
            'method' => 'post',
        ]);

        return $this->render(
            'Account/password.html.twig',
            [
                'user' => $user,
                'form' => $form,
            ]
        );
    }

    #[Route(path: '/account/password', name: 'account_password_submit', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(PasswordType::class, new Password());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->passwordUpdater->updatePassword($user, $form->getData()->getNewPassword());

            // Reencrypt the MailCrypt key with new password
            if ($user->hasMailCryptSecretBox()) {
                $this->mailCryptKeyHandler->update($user, $form->getData()->getPassword(), $form->getData()->getNewPassword());
            }

            $user->setPasswordChangeRequired(false);
            $user->eraseCredentials();

            $this->manager->flush();

            $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::PASSWORD_CHANGED);

            $this->addFlash('success', 'flashes.password-change-successful');

            return $this->redirectToRoute('account');
        }

        return $this->render(
            'Account/password.html.twig',
            [
                'user' => $user,
                'form' => $form,
            ]
        );
    }

    #[Route(path: '/account/delete', name: 'account_delete', methods: ['GET'])]
    public function delete(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserDeleteType::class, new Delete());

        return $this->render(
            'Account/delete.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/account/delete', name: 'account_delete_submit', methods: ['POST'])]
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
            'Account/delete.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/account/recovery-token', name: 'account_recovery_token', methods: ['GET'])]
    public function recoveryToken(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(RecoveryTokenType::class, new RecoveryToken(), [
            'action' => $this->generateUrl('account_recovery_token_submit'),
            'method' => 'post',
        ]);

        return $this->render('Account/recovery_token.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/account/recovery-token', name: 'account_recovery_token_submit', methods: ['POST'])]
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

            return $this->redirectToRoute('account_recovery_token_confirm', ['recovery_token' => $recoveryToken]);
        }

        return $this->render('Account/recovery_token.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/account/recovery-token/confirm', name: 'account_recovery_token_confirm', methods: ['GET'])]
    public function recoveryTokenAck(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(
            RecoveryTokenConfirmType::class,
            new RecoveryTokenConfirm(),
            [
                'action' => $this->generateUrl('account_recovery_token_confirm_submit'),
                'method' => 'post',
            ]
        );

        return $this->render('Account/recovery_token.html.twig',
            [
                'form' => $form,
                'user' => $user,
                'recovery_token' => $request->query->get('recovery_token'),
            ]
        );
    }

    #[Route(path: '/account/recovery-token/confirm', name: 'account_recovery_token_confirm_submit', methods: ['POST'])]
    public function recoveryTokenAckSubmit(Request $request): Response
    {
        $data = new RecoveryTokenConfirm();
        $form = $this->createForm(RecoveryTokenConfirmType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'flashes.recovery-token-ack');

            return $this->redirectToRoute('start');
        }

        return $this->render('Account/recovery_token.html.twig',
            [
                'form' => $form,
                'recovery_token' => $data->getRecoveryToken(),
            ]
        );
    }
}
