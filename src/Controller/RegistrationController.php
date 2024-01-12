<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Exception;
use App\Entity\User;
use App\Form\Model\RecoveryTokenAck;
use App\Form\Model\Registration;
use App\Form\RecoveryTokenAckType;
use App\Form\RegistrationType;
use App\Handler\RegistrationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RegistrationController extends AbstractController
{
    /**
     * RegistrationController constructor.
     */
    public function __construct(private RegistrationHandler $registrationHandler, private ManagerRegistry $doctrine, private TokenStorageInterface $tokenStorage)
    {
    }

    /**
     * @throws Exception
     */
    public function register(Request $request, string $voucher = null): Response
    {
        if (!$this->registrationHandler->isRegistrationOpen()) {
            return $this->render('Registration/closed.html.twig');
        }

        $registration = new Registration();
        // Set voucher value in form if given as parameter in route
        if (null !== $voucher) {
            $registration->setVoucher($voucher);
        }
        $form = $this->createForm(
            RegistrationType::class,
            $registration,
            [
                'action' => $this->generateUrl('register'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->registrationHandler->handle($registration);

                if (null !== $user = $this->doctrine->getRepository(User::class)->findByEmail($registration->getEmail())) {
                    $token = new UsernamePasswordToken($user, $user->getPassword(), 'default', $user->getRoles());
                    $this->tokenStorage->setToken($token);
                }

                $recoveryToken = $user->getPlainRecoveryToken();

                // We have fetched plainRecoveryToken, which we need to show and can now remove
                // all sensitive values from the user object
                $user->eraseCredentials();

                $recoveryTokenAck = new RecoveryTokenAck();
                $recoveryTokenAck->setRecoveryToken($recoveryToken);
                $recoveryTokenAckForm = $this->createForm(
                    RecoveryTokenAckType::class,
                    $recoveryTokenAck,
                    [
                        'action' => $this->generateUrl('register_recovery_token'),
                        'method' => 'post',
                    ]
                );

                return $this->render('Registration/recovery_token.html.twig',
                    [
                        'form' => $recoveryTokenAckForm->createView(),
                        'recovery_token' => $recoveryToken,
                    ]
                );
            }
        }

        return $this->render('Registration/register.html.twig', ['form' => $form->createView()]);
    }

    public function registerRecoveryTokenAck(Request $request): Response
    {
        $recoveryTokenAck = new RecoveryTokenAck();
        $recoveryTokenAckForm = $this->createForm(
            RecoveryTokenAckType::class,
            $recoveryTokenAck
        );

        if ('POST' === $request->getMethod()) {
            $recoveryTokenAckForm->handleRequest($request);

            if ($recoveryTokenAckForm->isSubmitted() && $recoveryTokenAckForm->isValid()) {
                return $this->redirect($this->generateUrl('register_welcome'));
            }

            return $this->render('Registration/recovery_token.html.twig',
                [
                    'form' => $recoveryTokenAckForm->createView(),
                    'recovery_token' => $recoveryTokenAck->getRecoveryToken(),
                ]
            );
        }

        return $this->redirectToRoute('register');
    }

    public function welcome(Request $request): Response
    {
        $request->getSession()->getFlashBag()->add('success', 'flashes.registration-successful');

        return $this->render('Registration/welcome.html.twig');
    }
}
