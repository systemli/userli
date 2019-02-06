<?php

namespace App\Controller;

use App\Form\Model\Registration;
use App\Form\Model\RecoveryTokenAck;
use App\Form\RecoveryTokenAckType;
use App\Form\RegistrationType;
use App\Handler\RegistrationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @author louis <louis@systemli.org>
 */
class RegistrationController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function registerAction(Request $request)
    {
        $registrationHandler = $this->getRegistrationHandler();

        if (!$registrationHandler->canRegister()) {
            return $this->render('Registration/closed.html.twig');
        }

        $registration = new Registration();
        $form = $this->createForm(
            RegistrationType::class,
            $registration,
            array(
                'action' => $this->generateUrl('register'),
                'method' => 'post',
            )
        );

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $registrationHandler->handle($registration);

                $manager = $this->get('doctrine')->getManager();

                if (null !== $user = $manager->getRepository('App:User')->findByEmail($registration->getEmail())) {
                    $token = new UsernamePasswordToken($user, $user->getPassword(), 'default', $user->getRoles());
                    $this->get('security.token_storage')->setToken($token);
                }

                $recoveryToken = $user->getPlainRecoveryToken();
                $user->erasePlainRecoveryToken();

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

        return $this->render('Registration/register.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function registerRecoveryTokenAckAction(Request $request): Response
    {
        $recoveryTokenAck = new RecoveryTokenAck();
        $recoveryTokenAckForm = $this->createForm(
            RecoveryTokenAckType::class,
            $recoveryTokenAck
        );

        if ('POST' === $request->getMethod()) {
            $recoveryTokenAckForm->handleRequest($request);

            if ($recoveryTokenAckForm->isSubmitted() and $recoveryTokenAckForm->isValid()) {
                return $this->redirect($this->generateUrl('register_welcome'));
            } else {
                return $this->render('Registration/recovery_token.html.twig',
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
     * @param Request $request
     *
     * @return Response
     */
    public function welcomeAction(Request $request): Response
    {
        $request->getSession()->getFlashBag()->add('success', 'flashes.registration-successful');

        return $this->render('Registration/welcome.html.twig');
    }

    /**
     * @return RegistrationHandler
     */
    private function getRegistrationHandler()
    {
        return $this->get('App\Handler\RegistrationHandler');
    }
}
