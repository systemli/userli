<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Event\RecoveryProcessEvent;
use App\Event\UserEvent;
use App\Form\Model\RecoveryProcess;
use App\Form\Model\RecoveryResetPassword;
use App\Form\Model\RecoveryTokenConfirm;
use App\Form\RecoveryProcessType;
use App\Form\RecoveryResetPasswordType;
use App\Form\RecoveryTokenConfirmType;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RecoveryController extends AbstractController
{
    private const PROCESS_DELAY = '-2 days';

    private const PROCESS_EXPIRE = '-30 days';

    public function __construct(
        private readonly PasswordUpdater $passwordUpdater,
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly RecoveryTokenHandler $recoveryTokenHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityManagerInterface $manager,
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
            $email = $data->email;
            $recoveryToken = $data->recoveryToken;

            // Validate the passed email + recoveryToken
            $user = $this->manager->getRepository(User::class)->findByEmail($email);

            if (null === $user || !$this->verifyEmailRecoveryToken($user, $recoveryToken)) {
                $this->addFlash('error', 'flashes.recovery-token-invalid');
            } else {
                $recoveryStartTime = $user->getRecoveryStartTime();

                if (null === $recoveryStartTime || new DateTime($this::PROCESS_EXPIRE) >= $recoveryStartTime) {
                    // Recovery process gets started
                    $user->updateRecoveryStartTime();
                    $this->manager->flush();
                    $this->eventDispatcher->dispatch(new UserEvent($user), RecoveryProcessEvent::NAME);
                    // We don't have to add two days here, they will get added in `RecoveryProcessMessageSender`
                    $recoveryActiveTime = $user->getRecoveryStartTime();
                } elseif (new DateTime($this::PROCESS_DELAY) < $recoveryStartTime) {
                    // Recovery process is pending, but waiting period didn't elapse yet
                    $recoveryActiveTime = $recoveryStartTime->add(new DateInterval('P2D'));
                } else {
                    $this->addFlash('recoveryToken', $recoveryToken);

                    // Recovery process successful, go on with the form to reset password
                    return $this->redirectToRoute('recovery_reset_password', [
                        'email' => $email,
                    ]);
                }

                return $this->render(
                    'Recovery/recovery_started.html.twig',
                    [
                        'form' => $form,
                        'active_time' => $recoveryActiveTime,
                    ]
                );
            }
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

            // Validate the passed email + recoveryToken
            $user = $this->manager->getRepository(User::class)->findByEmail($email);

            if (null !== $user && $this->verifyEmailRecoveryToken($user, $recoveryToken, true)) {
                if ($form->isValid()) {
                    // Success: change the password
                    $newRecoveryToken = $this->resetPassword($user, $data->getPlainPassword(), $recoveryToken);
                    $this->addFlash('success', 'flashes.recovery-password-changed');

                    // Cleanup variables with confidential content
                    sodium_memzero($recoveryToken);

                    return $this->redirectToRoute('recovery_recovery_token_ack', ['recoveryToken' => $newRecoveryToken]);
                }

                // Cleanup variables with confidential content
                sodium_memzero($recoveryToken);

                // Validation of new password pair failed, try again
                return $this->render(
                    'Recovery/reset_password.html.twig',
                    [
                        'form' => $form,
                    ]
                );
            }

            // Cleanup variables with confidential content
            sodium_memzero($recoveryToken);

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

    /**
     * @throws Exception
     */
    private function resetPassword(User $user, string $password, string $recoveryToken): string
    {
        $this->passwordUpdater->updatePassword($user, $password);

        $mailCryptPrivateKey = $this->recoveryTokenHandler->decrypt($user, $recoveryToken);

        // Encrypt MailCrypt private key from recoverySecretBox with new password
        $this->mailCryptKeyHandler->updateWithPrivateKey($user, $mailCryptPrivateKey, $password);

        // Clear old token
        $user->eraseRecoveryStartTime();
        $user->eraseRecoverySecretBox();

        // Generate new token
        $user->setPlainMailCryptPrivateKey($mailCryptPrivateKey);

        $this->recoveryTokenHandler->create($user);
        if (null === $newRecoveryToken = $user->getPlainRecoveryToken()) {
            throw new Exception('PlainRecoveryToken should not be null');
        }

        // Reset twofactor settings
        $user->setTotpConfirmed(false);
        $user->setTotpSecret(null);
        $user->clearBackupCodes();

        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();
        sodium_memzero($mailCryptPrivateKey);

        $this->manager->flush();

        return $newRecoveryToken;
    }

    /**
     * @throws Exception
     */
    private function verifyEmailRecoveryToken(User $user, string $recoveryToken, bool $verifyTime = false): bool
    {
        if ($verifyTime) {
            $recoveryStartTime = $user->getRecoveryStartTime();
            if (null === $recoveryStartTime || new DateTime($this::PROCESS_DELAY) < $recoveryStartTime) {
                return false;
            }
        }

        return $this->recoveryTokenHandler->verify($user, strtolower($recoveryToken));
    }
}
