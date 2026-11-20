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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RecoveryController extends AbstractController
{
    private const string SESSION_KEY = 'recovery_reset_password';

    private const string SESSION_NEW_TOKEN_KEY = 'recovery_new_recovery_token';

    /**
     * Seconds the user may stay on the reset password page after authenticating with email and recovery token.
     */
    private const int SESSION_TTL = 900;

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
                    $request->getSession(),
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
        $credentials = $this->getResetPasswordCredentials($request->getSession());
        if (null === $credentials) {
            // Page opened directly or authentication expired, start over
            $this->addFlash('info', 'flashes.recovery-session-expired');

            return $this->redirectToRoute('recovery');
        }

        [$email, $recoveryToken] = $credentials;

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
                    $session = $request->getSession();
                    $session->remove(self::SESSION_KEY);
                    // Kept in the session to keep it out of URLs (browser history, server logs)
                    $session->set(self::SESSION_NEW_TOKEN_KEY, $newRecoveryToken);
                    $this->addFlash('success', 'flashes.recovery-password-changed');

                    return $this->redirectToRoute('recovery_recovery_token_ack');
                }

                // Validation of new password pair failed, try again
                return $this->render('Recovery/reset_password.html.twig', ['form' => $form]);
            }

            // Verification of $email + $recoveryToken failed, start over
            $this->addFlash('error', 'flashes.recovery-reauthenticate');

            return $this->render('Recovery/reset_password.html.twig', ['form' => $form], new Response(status: Response::HTTP_UNAUTHORIZED));
        }

        return $this->render('Recovery/reset_password.html.twig', ['form' => $form]);
    }

    #[Route(path: '/recovery/recovery_token/ack', name: 'recovery_recovery_token_ack', methods: ['GET'])]
    public function recoveryRecoveryTokenAck(Request $request): Response
    {
        $recoveryToken = $request->getSession()->get(self::SESSION_NEW_TOKEN_KEY);
        if (!is_string($recoveryToken)) {
            // Page opened directly or token already acknowledged
            return $this->redirectToRoute('login');
        }

        $form = $this->createForm(RecoveryTokenConfirmType::class, new RecoveryTokenConfirm(), [
            'action' => $this->generateUrl('recovery_recovery_token_ack_submit'),
            'method' => 'post',
        ]);

        return $this->render('Recovery/recovery_token.html.twig', [
            'form' => $form,
            'recovery_token' => $recoveryToken,
        ]);
    }

    #[Route(path: '/recovery/recovery_token/ack', name: 'recovery_recovery_token_ack_submit', methods: ['POST'])]
    public function recoveryRecoveryTokenAckSubmit(Request $request): Response
    {
        $session = $request->getSession();
        $recoveryToken = $session->get(self::SESSION_NEW_TOKEN_KEY);
        if (!is_string($recoveryToken)) {
            return $this->redirectToRoute('login');
        }

        $form = $this->createForm(RecoveryTokenConfirmType::class, new RecoveryTokenConfirm());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session->remove(self::SESSION_NEW_TOKEN_KEY);
            $this->addFlash('success', 'flashes.recovery-token-ack');
            $this->addFlash('success', 'flashes.recovery-next-login');

            return $this->redirectToRoute('login');
        }

        return $this->render('Recovery/recovery_token.html.twig', [
            'form' => $form,
            'recovery_token' => $recoveryToken,
        ]);
    }

    private function renderRecoveryFormWithError(mixed $form): Response
    {
        $this->addFlash('error', 'flashes.recovery-token-invalid');

        return $this->render('Recovery/recovery_new.html.twig', ['form' => $form], new Response(status: Response::HTTP_UNAUTHORIZED));
    }

    private function redirectToResetPassword(SessionInterface $session, string $email, string $recoveryToken): Response
    {
        // Kept in the session (not a flash message) so that reloading the page keeps working
        $session->set(self::SESSION_KEY, [
            'email' => $email,
            'recoveryToken' => $recoveryToken,
            'verifiedAt' => time(),
        ]);

        return $this->redirectToRoute('recovery_reset_password');
    }

    /**
     * Returns email and recovery token if the user authenticated recently, null otherwise.
     *
     * @return array{0: string, 1: string}|null
     */
    private function getResetPasswordCredentials(SessionInterface $session): ?array
    {
        $data = $session->get(self::SESSION_KEY);

        if (!is_array($data)
            || !isset($data['email'], $data['recoveryToken'], $data['verifiedAt'])
            || !is_string($data['email'])
            || !is_string($data['recoveryToken'])
            || !is_int($data['verifiedAt'])
            || $data['verifiedAt'] < time() - self::SESSION_TTL
        ) {
            $session->remove(self::SESSION_KEY);

            return null;
        }

        return [$data['email'], $data['recoveryToken']];
    }
}
