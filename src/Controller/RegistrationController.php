<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
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
    public function __construct(private readonly RegistrationHandler $registrationHandler, private readonly ManagerRegistry $manager, private readonly TokenStorageInterface $tokenStorage)
    {
    }

    #[Route(path: '/register/recovery_token', name: 'register_recovery_token')]
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

    #[Route(path: '/register/welcome', name: 'register_welcome')]
    public function welcome(Request $request): Response
    {
        $request->getSession()->getFlashBag()->add('success', 'flashes.registration-successful');

        return $this->render('Registration/welcome.html.twig');
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/register', name: 'register')]
    #[Route(path: '/register/{voucher}', name: 'register_voucher')]
    public function register(Request $request, string $voucher = ''): Response
    {
        if (!$this->registrationHandler->isRegistrationOpen()) {
            return $this->render('Registration/closed.html.twig');
        }

        $registration = new Registration();
        $registration->setVoucher($voucher);

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
                $user = $this->registrationHandler->handle($registration);

                $token = new UsernamePasswordToken($user, 'default', $user->getRoles());
                $this->tokenStorage->setToken($token);

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
}
