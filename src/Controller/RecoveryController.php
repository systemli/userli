<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\Events;
use App\Event\UserEvent;
use App\Form\Model\RecoveryProcess;
use App\Form\Model\RecoveryResetPassword;
use App\Form\Model\RecoveryToken;
use App\Form\Model\RecoveryTokenAck;
use App\Form\RecoveryProcessType;
use App\Form\RecoveryResetPasswordType;
use App\Form\RecoveryTokenAckType;
use App\Form\RecoveryTokenType;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author doobry <doobry@systemli.org>
 */
class RecoveryController extends Controller
{
    /**
     * @var PasswordUpdater
     */
    private $passwordUpdater;
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
     *
     * @param PasswordUpdater          $passwordUpdater
     * @param RecoveryTokenHandler     $recoveryTokenHandler
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(PasswordUpdater $passwordUpdater, RecoveryTokenHandler $recoveryTokenHandler, EventDispatcherInterface $eventDispatcher)
    {
        $this->passwordUpdater = $passwordUpdater;
        $this->recoveryTokenHandler = $recoveryTokenHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function recoveryProcessAction(Request $request): Response
    {
        $processStarted = false;
        $recoveryActiveTime = new \DateTime();
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

                    if (null === $recoveryStartTime || new \DateTime('-30 days') >= $recoveryStartTime) {
                        // Recovery process gets started
                        $processStarted = true;
                        $user->updateRecoveryStartTime();
                        $this->getDoctrine()->getManager()->flush();
                        $this->eventDispatcher->dispatch(Events::RECOVERY_PROCESS_STARTED, new UserEvent($user));
                        $recoveryActiveTime = $user->getRecoveryStartTime()->add(new \DateInterval('P2D'));
                    } elseif (new \DateTime('-2 days') <= $recoveryStartTime) {
                        // Recovery process is pending, but waiting period didn't elapse yet
                        $processStarted = true;
                        $recoveryActiveTime = $recoveryStartTime->add(new \DateInterval('P2D'));
                    } else {
                        // Recovery process successful, go on with the form to reset password
                        // Pass $email and $recoveryToken as hidden field values for verification by recoveryResetPasswordAction
                        $recoveryResetPassword = new RecoveryResetPassword();
                        $recoveryResetPassword->setEmail($user->getEmail());
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
                }
            }
        }

        return $this->render(
            'Recovery/recovery.html.twig',
            [
                'form' => $recoveryForm->createView(),
                'process_started' => $processStarted,
                'active_time' => $recoveryActiveTime,
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
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

                if (null !== $user && $this->verifyEmailRecoveryToken($user, $recoveryToken)) {
                    if ($recoveryResetPasswordForm->isValid()) {
                        // Success, change the password and redirect to login page
                        $this->resetPassword($user, $recoveryResetPassword->newPassword);
                        $request->getSession()->getFlashBag()->add('success', 'flashes.recovery-password-changed');

                        return $this->redirect($this->generateUrl('login'));
                    } else {
                        // Validation of new password pair failed, try again
                        return $this->render(
                            'Recovery/reset_password.html.twig',
                            [
                                'form' => $recoveryResetPasswordForm->createView(),
                            ]
                        );
                    }
                } else {
                    // Verification of $email + $recoveryToken failed, start over
                    $request->getSession()->getFlashBag()->add('error', 'flashes.recovery-reauthenticate');
                }
            }
        }

        return $this->redirect($this->generateUrl('recovery'));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function recoveryTokenAction(Request $request): Response
    {
        $user = $this->getUser();

        $recoveryTokenObject = new RecoveryToken();
        $form = $this->createForm(RecoveryTokenType::class, $recoveryTokenObject);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setPlainPassword($recoveryTokenObject->password);

                $this->recoveryTokenHandler->create($user);
                $recoveryToken = $user->getPlainRecoveryToken();
                $user->eraseToken();

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
                        'recovery_secret_set' => $user->hasRecoverySecret(),
                    ]
                );
            }
        }

        $recoveryTokenAckForm = $this->createForm(
            RecoveryTokenAckType::class,
            new RecoveryTokenAck(),
            [
                'action' => $this->generateUrl('user_recovery_token_ack'),
                'method' => 'post',
            ]
        );

        return $this->render('User/recovery_token.html.twig',
            [
                'form' => $form->createView(),
                'recovery_secret_set' => $user->hasRecoverySecret(),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
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
            } else {
                return $this->render('User/recovery_token.html.twig',
                    [
                        'form' => $recoveryTokenAckForm->createView(),
                        'recovery_token' => $recoveryTokenAck->getRecoveryToken(),
                    ]
                );
            }
        }

        return $this->redirectToRoute('register');
    }

    /**
     * @param User   $user
     * @param string $password
     */
    private function resetPassword(User $user, string $password)
    {
        $user->setPlainPassword($password);
        $this->passwordUpdater->updatePassword($user);
        $user->eraseCredentials();
        $this->getDoctrine()->getManager()->flush();
    }

    /**
     * @param User   $user
     * @param string $recoveryToken
     *
     * @return bool
     */
    private function verifyEmailRecoveryToken(User $user, string $recoveryToken): bool
    {
        return ($this->recoveryTokenHandler->verify($user, strtolower($recoveryToken))) ? true : false;
    }
}
