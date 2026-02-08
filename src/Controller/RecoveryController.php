<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\RecoveryStatus;
use App\Form\Model\RecoveryProcess;
use App\Form\Model\RecoveryResetPassword;
use App\Form\Model\RecoveryTokenConfirm;
use App\Form\RecoveryProcessType;
use App\Form\RecoveryResetPasswordType;
use App\Form\RecoveryTokenConfirmType;
use App\Handler\RecoveryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\InvalidParameterException;

final class RecoveryController extends AbstractController
{
    public function __construct(
        private readonly RecoveryHandler $recoveryHandler,
    ) {
    }

    #[Route(path: '/recovery', name: 'recovery', methods: ['GET'])]
    public function recovery(): Response
    {
        $data = new RecoveryProcess();
        $form = $this->createForm(RecoveryProcessType::class, $data, [
            'action' => $this->generateUrl('recovery_submit'),
            'method' => 'post',
        ]);

        return $this->render('Recovery/recovery_new.html.twig', ['form' => $form]);
    }

    #[Route(path: '/recovery', name: 'recovery_submit', methods: ['POST'])]
    public function recoverySubmit(Request $request): Response
    {
        $data = new RecoveryProcess();
        $form = $this->createForm(RecoveryProcessType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->recoveryHandler->startRecovery(
                $data->getEmail(),
                $data->getRecoveryToken(),
            );

            return match ($result->status) {
                RecoveryStatus::Invalid => $this->renderRecoveryFormWithError($form),
                RecoveryStatus::Started, RecoveryStatus::Pending => $this->render(
                    'Recovery/recovery_started.html.twig',
                    [
                        'form' => $form,
                        'active_time' => $result->activeTime,
                    ],
                ),
                RecoveryStatus::Ready => $this->redirectToResetPassword(
                    $data->getEmail(),
                    $result->recoveryToken,
                ),
            };
        }

        return $this->render('Recovery/recovery_new.html.twig', ['form' => $form]);
    }

    #[Route(path: '/recovery/reset_password', name: 'recovery_reset_password', methods: ['GET'])]
    public function recoveryResetPassword(Request $request): Response
    {
        $email = $request->query->get('email');
        $session = $request->getSession();
        assert($session instanceof Session);
        $recoveryToken = $session->getFlashBag()->get('recoveryToken')[0];
        if (null === $email || null === $recoveryToken) {
            throw new InvalidParameterException('Email and recoveryToken must be provided');
        }

        $data = new RecoveryResetPassword();
        $data->setEmail($email);
        $data->setRecoveryToken($recoveryToken);

        $form = $this->createForm(RecoveryResetPasswordType::class, $data, [
            'action' => $this->generateUrl('recovery_reset_password_submit'),
            'method' => 'post',
        ]);

        return $this->render('Recovery/reset_password.html.twig', ['form' => $form]);
    }

    #[Route(path: '/recovery/reset_password', name: 'recovery_reset_password_submit', methods: ['POST'])]
    public function recoveryResetPasswordSubmit(Request $request): Response
    {
        $data = new RecoveryResetPassword();
        $form = $this->createForm(RecoveryResetPasswordType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $email = $data->getEmail();
            $recoveryToken = $data->getRecoveryToken();

            if ($this->recoveryHandler->verifyRecoveryToken($email, $recoveryToken, true)) {
                if ($form->isValid()) {
                    $newRecoveryToken = $this->recoveryHandler->resetPassword($email, $recoveryToken, $data->getPassword());
                    $this->addFlash('success', 'flashes.recovery-password-changed');

                    return $this->redirectToRoute('recovery_recovery_token_ack', ['recoveryToken' => $newRecoveryToken]);
                }

                // Validation of new password pair failed, try again
                return $this->render('Recovery/reset_password.html.twig', ['form' => $form]);
            }

            // Verification of $email + $recoveryToken failed, start over
            $this->addFlash('error', 'flashes.recovery-reauthenticate');
        }

        return $this->render('Recovery/reset_password.html.twig', ['form' => $form]);
    }

    #[Route(path: '/recovery/recovery_token/ack', name: 'recovery_recovery_token_ack', methods: ['GET'])]
    public function recoveryRecoveryTokenAck(Request $request): Response
    {
        $recoveryToken = $request->query->get('recoveryToken');
        if (null === $recoveryToken) {
            throw new InvalidParameterException('Recovery token must be provided');
        }

        $data = new RecoveryTokenConfirm();
        $data->setRecoveryToken($recoveryToken);

        $form = $this->createForm(RecoveryTokenConfirmType::class, $data, [
            'action' => $this->generateUrl('recovery_recovery_token_ack_submit'),
            'method' => 'post',
        ]);

        return $this->render('Recovery/recovery_token.html.twig', [
            'form' => $form,
            'recovery_token' => $data->getRecoveryToken(),
        ]);
    }

    #[Route(path: '/recovery/recovery_token/ack', name: 'recovery_recovery_token_ack_submit', methods: ['POST'])]
    public function recoveryRecoveryTokenAckSubmit(Request $request): Response
    {
        $data = new RecoveryTokenConfirm();
        $form = $this->createForm(RecoveryTokenConfirmType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'flashes.recovery-token-ack');
            $this->addFlash('success', 'flashes.recovery-next-login');

            return $this->redirectToRoute('login');
        }

        return $this->render('Recovery/recovery_token.html.twig', [
            'form' => $form,
            'recovery_token' => $data->getRecoveryToken(),
        ]);
    }

    private function renderRecoveryFormWithError(mixed $form): Response
    {
        $this->addFlash('error', 'flashes.recovery-token-invalid');

        return $this->render('Recovery/recovery_new.html.twig', ['form' => $form]);
    }

    private function redirectToResetPassword(string $email, string $recoveryToken): Response
    {
        $this->addFlash('recoveryToken', $recoveryToken);

        return $this->redirectToRoute('recovery_reset_password', ['email' => $email]);
    }
}
