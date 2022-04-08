<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\RecoveryProcessEvent;
use App\Event\UserEvent;
use App\Form\Model\RecoveryProcess;
use App\Form\Model\RecoveryResetPassword;
use App\Form\Model\RecoveryToken;
use App\Form\Model\RecoveryTokenAck;
use App\Form\RecoveryProcessType;
use App\Form\RecoveryResetPasswordType;
use App\Form\RecoveryTokenAckType;
use App\Form\RecoveryTokenType;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RecoveryController extends AbstractController
{
    private const PROCESS_DELAY = '-2 days';
    private const PROCESS_EXPIRE = '-30 days';

    /**
     * @var PasswordUpdater
     */
    private $passwordUpdater;
    /**
     * @var MailCryptKeyHandler
     */
    private $mailCryptKeyHandler;
    /**
     * @var RecoveryTokenHandler
     */
    private $recoveryTokenHandler;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * RecoveryController constructor.
     */
    public function __construct(PasswordUpdater $passwordUpdater, MailCryptKeyHandler $mailCryptKeyHandler, RecoveryTokenHandler $recoveryTokenHandler, EventDispatcherInterface $eventDispatcher)
    {
        $this->passwordUpdater = $passwordUpdater;
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
        $this->recoveryTokenHandler = $recoveryTokenHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws \Exception
     */
    public function recoveryProcessAction(Request $request): Response
    {
        $recoveryProcess = new RecoveryProcess();
        $recoveryForm = $this->createForm(RecoveryProcessType::class, $recoveryProcess);

        if ('POST' === $request->getMethod()) {
            $recoveryForm->handleRequest($request);

            if ($recoveryForm->isSubmitted() && $recoveryForm->isValid()) {
                $email = $recoveryProcess->email;
                $recoveryToken = $recoveryProcess->recoveryToken;

                // Validate the passed email + recoveryToken

                $userRepository = $this->get('doctrine')->getRepository('App:User');
                $user = $userRepository->findByEmail($email);

                if (null === $user || !$this->verifyEmailRecoveryToken($user, $recoveryToken)) {
                    $request->getSession()->getFlashBag()->add('error', 'flashes.recovery-token-invalid');
                } else {
                    $recoveryStartTime = $user->getRecoveryStartTime();

                    if (null === $recoveryStartTime || new \DateTime($this::PROCESS_EXPIRE) >= $recoveryStartTime) {
                        // Recovery process gets started
                        $user->updateRecoveryStartTime();
                        $this->getDoctrine()->getManager()->flush();
                        $this->eventDispatcher->dispatch(new UserEvent($user), RecoveryProcessEvent::NAME);
                        $recoveryActiveTime = $user->getRecoveryStartTime()->add(new \DateInterval('P2D'));
                    } elseif (new \DateTime($this::PROCESS_DELAY) < $recoveryStartTime) {
                        // Recovery process is pending, but waiting period didn't elapse yet
                        $recoveryActiveTime = $recoveryStartTime->add(new \DateInterval('P2D'));
                    } else {
                        // Recovery process successful, go on with the form to reset password
                        return $this->renderResetPasswordForm($user, $recoveryToken);
                    }

                    return $this->render(
                        'Recovery/recovery_started.html.twig',
                        [
                            'form' => $recoveryForm->createView(),
                            'active_time' => $recoveryActiveTime,
                        ]
                    );
                }
            }
        }

        return $this->render(
            'Recovery/recovery_new.html.twig',
            [
                'form' => $recoveryForm->createView(),
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function recoveryResetPasswordAction(Request $request): Response
    {
        $recoveryResetPassword = new RecoveryResetPassword();
        $recoveryResetPasswordForm = $this->createForm(
            RecoveryResetPasswordType::class,
            $recoveryResetPassword,
            [
                'action' => $this->generateUrl('recovery_reset_password'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $recoveryResetPasswordForm->handleRequest($request);

            if ($recoveryResetPasswordForm->isSubmitted()) {
                $email = $recoveryResetPassword->getEmail();
                $recoveryToken = $recoveryResetPassword->getRecoveryToken();

                // Validate the passed email + recoveryToken

                $userRepository = $this->get('doctrine')->getRepository('App:User');
                $user = $userRepository->findByEmail($email);

                if (null !== $user && $this->verifyEmailRecoveryToken($user, $recoveryToken, true)) {
                    if ($recoveryResetPasswordForm->isValid()) {
                        // Success: change the password
                        $newRecoveryToken = $this->resetPassword($user, $recoveryResetPassword->getPlainPassword(), $recoveryToken);
                        $request->getSession()->getFlashBag()->add('success', 'flashes.recovery-password-changed');

                        $recoveryTokenAck = new RecoveryTokenAck();
                        $recoveryTokenAck->setRecoveryToken($recoveryToken);
                        $recoveryTokenAckForm = $this->createForm(
                            RecoveryTokenAckType::class,
                            $recoveryTokenAck,
                            [
                                'action' => $this->generateUrl('recovery_recovery_token_ack'),
                                'method' => 'post',
                            ]
                        );

                        // Cleanup variables with confidential content
                        sodium_memzero($recoveryToken);

                        return $this->render('Recovery/recovery_token.html.twig',
                            [
                                'form' => $recoveryTokenAckForm->createView(),
                                'recovery_token' => $newRecoveryToken,
                            ]
                        );
                    }

                    // Cleanup variables with confidential content
                    sodium_memzero($recoveryToken);

                    // Validation of new password pair failed, try again
                    return $this->render(
                        'Recovery/reset_password.html.twig',
                        [
                            'form' => $recoveryResetPasswordForm->createView(),
                        ]
                    );
                }

                // Cleanup variables with confidential content
                sodium_memzero($recoveryToken);

                // Verification of $email + $recoveryToken failed, start over
                $request->getSession()->getFlashBag()->add('error', 'flashes.recovery-reauthenticate');
            }
        }

        return $this->redirect($this->generateUrl('recovery'));
    }

    /**
     * @throws \Exception
     */
    public function recoveryTokenAction(Request $request): Response
    {
        if (null === $user = $this->getUser()) {
            throw new \Exception('User should not be null');
        }

        $recoveryTokenModel = new RecoveryToken();
        $form = $this->createForm(RecoveryTokenType::class, $recoveryTokenModel);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setPlainPassword($recoveryTokenModel->password);

                // Check if user has a MailCrypt key
                if ($user->hasMailCryptSecretBox()) {
                    // Decrypt the MailCrypt key
                    $user->setPlainMailCryptPrivateKey($this->mailCryptKeyHandler->decrypt($user, $recoveryTokenModel->password));
                } else {
                    // Create a new MailCrypt key if none existed before
                    $this->mailCryptKeyHandler->create($user);
                }

                // Generate a new recovery token and encrypt the MailCrypt key with it
                $this->recoveryTokenHandler->create($user);
                if (null === $recoveryToken = $user->getPlainRecoveryToken()) {
                    throw new \Exception('recoveryToken should not be null');
                }

                // Erase sensitive plaintext data from User object
                $user->erasePlainMailCryptPrivateKey();
                $user->erasePlainRecoveryToken();
                $user->eraseCredentials();

                $recoveryTokenAck = new RecoveryTokenAck();
                $recoveryTokenAck->setRecoveryToken($recoveryToken);
                $recoveryTokenAckForm = $this->createForm(
                    RecoveryTokenAckType::class,
                    $recoveryTokenAck,
                    [
                        'action' => $this->generateUrl('user_recovery_token_ack'),
                        'method' => 'post',
                    ]
                );

                return $this->render('User/recovery_token.html.twig',
                    [
                        'form' => $recoveryTokenAckForm->createView(),
                        'recovery_token' => $recoveryToken,
                        'recovery_secret_set' => $user->hasRecoverySecretBox(),
                        'user' => $user,
                    ]
                );
            }
        }

        return $this->render('User/recovery_token.html.twig',
            [
                'form' => $form->createView(),
                'recovery_secret_set' => $user->hasRecoverySecretBox(),
                'user' => $user,
            ]
        );
    }

    public function recoveryRecoveryTokenAckAction(Request $request): Response
    {
        $recoveryTokenAck = new RecoveryTokenAck();
        $recoveryTokenAckForm = $this->createForm(
            RecoveryTokenAckType::class,
            $recoveryTokenAck,
            [
                'action' => $this->generateUrl('recovery_recovery_token_ack'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $recoveryTokenAckForm->handleRequest($request);

            if ($recoveryTokenAckForm->isSubmitted() and $recoveryTokenAckForm->isValid()) {
                $request->getSession()->getFlashBag()->add('success', 'flashes.recovery-token-ack');
                $request->getSession()->getFlashBag()->add('success', 'flashes.recovery-next-login');

                return $this->redirect($this->generateUrl('login'));
            }

            return $this->render('Recovery/recovery_token.html.twig',
                [
                    'form' => $recoveryTokenAckForm->createView(),
                    'recovery_token' => $recoveryTokenAck->getRecoveryToken(),
                ]
            );
        }

        return $this->redirectToRoute('recovery');
    }

    public function recoveryTokenAckAction(Request $request): Response
    {
        $recoveryTokenAck = new RecoveryTokenAck();
        $recoveryTokenAckForm = $this->createForm(
            RecoveryTokenAckType::class,
            $recoveryTokenAck,
            [
                'action' => $this->generateUrl('user_recovery_token_ack'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $recoveryTokenAckForm->handleRequest($request);

            if ($recoveryTokenAckForm->isSubmitted() and $recoveryTokenAckForm->isValid()) {
                $request->getSession()->getFlashBag()->add('success', 'flashes.recovery-token-ack');

                return $this->redirect($this->generateUrl('index'));
            }

            return $this->render('User/recovery_token.html.twig',
                [
                    'form' => $recoveryTokenAckForm->createView(),
                    'recovery_token' => $recoveryTokenAck->getRecoveryToken(),
                ]
            );
        }

        return $this->redirectToRoute('register');
    }

    /**
     * @throws \Exception
     */
    private function renderResetPasswordForm(User $user, string $recoveryToken): Response
    {
        // Pass $email and $recoveryToken as hidden field values for verification by recoveryResetPasswordAction
        $recoveryResetPassword = new RecoveryResetPassword();
        if (null === $email = $user->getEmail()) {
            throw new \Exception('email should not be null');
        }
        $recoveryResetPassword->setEmail($email);
        $recoveryResetPassword->setRecoveryToken($recoveryToken);
        $recoveryResetPasswordForm = $this->createForm(
            RecoveryResetPasswordType::class,
            $recoveryResetPassword,
            [
                'action' => $this->generateUrl('recovery_reset_password'),
                'method' => 'post',
            ]
        );

        return $this->render(
            'Recovery/reset_password.html.twig',
            [
                'form' => $recoveryResetPasswordForm->createView(),
            ]
        );
    }

    /**
     * @throws \Exception
     */
    private function resetPassword(User $user, string $password, string $recoveryToken): string
    {
        $user->setPlainPassword($password);
        $this->passwordUpdater->updatePassword($user);

        $mailCryptPrivateKey = $this->recoveryTokenHandler->decrypt($user, $recoveryToken);

        // Encrypt MailCrypt private key from recoverySecretBox with new password
        $this->mailCryptKeyHandler->updateWithPrivateKey($user, $mailCryptPrivateKey);

        // Clear old token
        $user->eraseRecoveryStartTime();
        $user->eraseRecoverySecretBox();

        // Generate new token
        $user->setPlainMailCryptPrivateKey($mailCryptPrivateKey);
        $this->recoveryTokenHandler->create($user);
        if (null === $newRecoveryToken = $user->getPlainRecoveryToken()) {
            throw new \Exception('PlainRecoveryToken should not be null');
        }

        $user->erasePlainRecoveryToken();
        $user->erasePlainMailCryptPrivateKey();
        $user->eraseCredentials();
        sodium_memzero($mailCryptPrivateKey);

        $this->getDoctrine()->getManager()->flush();

        return $newRecoveryToken;
    }

    /**
     * @throws \Exception
     */
    private function verifyEmailRecoveryToken(User $user, string $recoveryToken, bool $verifyTime = false): bool
    {
        if ($verifyTime) {
            $recoveryStartTime = $user->getRecoveryStartTime();
            if (null === $recoveryStartTime || new \DateTime($this::PROCESS_DELAY) < $recoveryStartTime) {
                return false;
            }
        }

        return $this->recoveryTokenHandler->verify($user, strtolower($recoveryToken));
    }
}
