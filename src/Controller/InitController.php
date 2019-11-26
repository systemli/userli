<?php

namespace App\Controller;

use App\Form\Model\PlainPassword;
use App\Form\PlainPasswordType;
use App\Helper\AdminPasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class InitController.
 */
class InitController extends AbstractController
{
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var AdminPasswordUpdater
     */
    private $updater;

    public function __construct(ObjectManager $manager, AdminPasswordUpdater $updater)
    {
        $this->manager = $manager;
        $this->updater = $updater;
    }

    /**
     * @return Response
     */
    public function indexAction(Request $request)
    {
        // redirect if already configured
        if (0 < $this->manager->getRepository('App:Domain')->count([])) {
            return $this->redirectToRoute('index');
        }

        $password = new PlainPassword();
        $passwordForm = $this->createForm(
            PlainPasswordType::class,
            $password,
            [
                'action' => $this->generateUrl('init'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $passwordForm->handleRequest($request);

            if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
                $this->updater->updateAdminPassword($password->newPassword);
                $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');

                return $this->redirectToRoute('index');
            }
        }

        return $this->render('init.html.twig', ['form' => $passwordForm->createView()]);
    }
}
