<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RecoveryResult;
use App\Enum\RecoveryStatus;
use App\Form\Model\RecoveryProcess;
use App\Form\Model\RecoveryResetPassword;
use App\Form\Model\RecoveryTokenConfirm;
use App\Form\RecoveryProcessType;
use App\Form\RecoveryResetPasswordType;
use App\Form\RecoveryTokenConfirmType;
use App\Handler\RecoveryHandler;
use App\Repository\UserRepository;
use App\Service\RecoveryCancelLinkService;
use DateTimeImmutable;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
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
        private readonly UserRepository $userRepository,
        private readonly Security $security,
        private readonly RecoveryCancelLinkService $cancelLinkService,
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

        return $this->renderRecoveryForm($form);
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
                RecoveryStatus::Started, RecoveryStatus::Pending => $this->renderRecoveryStarted($form, $result),
                RecoveryStatus::Ready => $this->redirectToResetPassword(
                    $request->getSession(),
                    $data->getEmail(),
                    $result->recoveryToken,
                ),
            };
        }

        return $this->renderRecoveryForm($form);
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
                    $this->logInUser($email);

                    // Session is migrated on login, so only fetch it afterwards
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

            // Verification of $email + $recoveryToken failed (e.g. process expired), start over
            $request->getSession()->remove(self::SESSION_KEY);
            $this->addFlash('error', 'flashes.recovery-reauthenticate');

            return $this->redirectToRoute('recovery');
        }

        return $this->render('Recovery/reset_password.html.twig', ['form' => $form]);
    }

    #[Route(path: '/recovery/recovery_token/ack', name: 'recovery_recovery_token_ack', methods: ['GET'])]
    public function recoveryRecoveryTokenAck(Request $request): Response
    {
        $recoveryToken = $request->getSession()->get(self::SESSION_NEW_TOKEN_KEY);
        if (!is_string($recoveryToken)) {
            // Page opened directly or token already acknowledged
            return $this->redirectToRoute('account');
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
            return $this->redirectToRoute('account');
        }

        $form = $this->createForm(RecoveryTokenConfirmType::class, new RecoveryTokenConfirm());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session->remove(self::SESSION_NEW_TOKEN_KEY);
            $this->addFlash('success', 'flashes.recovery-token-ack');
            $this->addFlash('info', 'flashes.recovery-reset-hint');

            return $this->redirectToRoute('account');
        }

        return $this->render('Recovery/recovery_token.html.twig', [
            'form' => $form,
            'recovery_token' => $recoveryToken,
        ]);
    }

    /**
     * Linked from the notification mail, so the account owner can cancel without signing in.
     *
     * Only shows a confirmation: mail scanners open links, so GET must not change anything.
     */
    #[Route(path: '/recovery/cancel', name: 'recovery_cancel', methods: ['GET'])]
    public function recoveryCancel(Request $request): Response
    {
        $user = $this->cancelLinkService->resolve($request);
        if (null === $user) {
            return $this->renderRecoveryCancel('invalid', Response::HTTP_NOT_FOUND);
        }

        return $this->render('Recovery/cancel.html.twig', [
            'state' => 'confirm',
            'email' => $user->getEmail(),
            'started' => $user->getRecoveryStartTime(),
        ]);
    }

    #[Route(path: '/recovery/cancel', name: 'recovery_cancel_submit', methods: ['POST'])]
    public function recoveryCancelSubmit(Request $request): Response
    {
        $user = $this->cancelLinkService->resolve($request);
        if (null === $user || !$this->isCsrfTokenValid('recovery_cancel', $request->request->getString('_token'))) {
            return $this->renderRecoveryCancel('invalid', Response::HTTP_NOT_FOUND);
        }

        $this->recoveryHandler->cancelRecovery($user);

        return $this->renderRecoveryCancel('cancelled');
    }

    private function renderRecoveryCancel(string $state, int $status = Response::HTTP_OK): Response
    {
        return $this->render('Recovery/cancel.html.twig', ['state' => $state], new Response(status: $status));
    }

    private function renderRecoveryStarted(FormInterface $form, RecoveryResult $result): Response
    {
        $activeTime = $result->activeTime ?? throw new LogicException('Started or pending recovery must have an active time');

        return $this->render('Recovery/recovery_started.html.twig', [
            'form' => $form,
            'active_time' => $activeTime,
            'delay_hours' => RecoveryHandler::PROCESS_DELAY_HOURS,
            'pending' => RecoveryStatus::Pending === $result->status,
            'time_left' => self::timeLeft($activeTime),
        ]);
    }

    private function renderRecoveryFormWithError(FormInterface $form): Response
    {
        $this->addFlash('error', 'flashes.recovery-token-invalid');

        return $this->renderRecoveryForm($form, Response::HTTP_UNAUTHORIZED);
    }

    private function renderRecoveryForm(FormInterface $form, int $status = Response::HTTP_OK): Response
    {
        return $this->render('Recovery/recovery_new.html.twig', [
            'form' => $form,
            'delay_hours' => RecoveryHandler::PROCESS_DELAY_HOURS,
        ], new Response(status: $status));
    }

    /**
     * Logs in the user after a successful password reset.
     *
     * The user proved ownership with email, recovery token and the waiting period, and just set a new password.
     */
    private function logInUser(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if (null === $user) {
            throw new LogicException('User should exist after resetting the password');
        }

        $this->security->login($user, 'form_login');
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

    /**
     * @return array{hours: int, minutes: int}
     */
    private static function timeLeft(DateTimeImmutable $activeTime): array
    {
        // Round up, so "0 h 0 min" is never shown while still waiting
        $minutesLeft = max(0, (int) ceil(($activeTime->getTimestamp() - time()) / 60));

        return ['hours' => intdiv($minutesLeft, 60), 'minutes' => $minutesLeft % 60];
    }
}
