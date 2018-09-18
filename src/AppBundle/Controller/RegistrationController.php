<?php

namespace AppBundle\Controller;

use AppBundle\Form\Model\Registration;
use AppBundle\Form\RegistrationType;
use AppBundle\Handler\RegistrationHandler;
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

            if ($form->isValid()) {
                $registrationHandler->handle($registration);

                $manager = $this->get('doctrine')->getManager();

                if (null !== $user = $manager->getRepository('AppBundle:User')->findByEmail($registration->getEmail())) {
                    $token = new UsernamePasswordToken($user, $user->getPassword(), 'default', $user->getRoles());
                    $this->get('security.token_storage')->setToken($token);
                }

                $request->getSession()->getFlashBag()->add('success', 'flashes.registration-successful');

                return $this->redirect($this->generateUrl('welcome'));
            }
        }

        return $this->render('Registration/register.html.twig', array('form' => $form->createView()));
    }

    /**
     * @return Response
     */
    public function welcomeAction()
    {
        return $this->render('Registration/welcome.html.twig');
    }

    /**
     * @return RegistrationHandler
     */
    private function getRegistrationHandler()
    {
        return $this->get('AppBundle\Handler\RegistrationHandler');
    }
}
