<?php

namespace App\Controller;

use App\Creator\DomainCreator;
use App\Entity\Domain;
use App\Entity\User;
use App\Form\DomainCreateType;
use App\Form\Model\DomainCreate;
use App\Form\Model\PlainPassword;
use App\Form\PlainPasswordType;
use App\Helper\AdminPasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InitController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly AdminPasswordUpdater   $updater,
        private readonly DomainCreator          $creator
    )
    {
    }

    #[Route(path: '/init', name: 'init', methods: ['GET'])]
    public function init(Request $request): Response
    {
        // redirect if already configured
        if (0 < $this->manager->getRepository(Domain::class)->count([])) {
            return $this->redirectToRoute('init_user');
        }

        $form = $this->createForm(DomainCreateType::class, new DomainCreate(), [
            'action' => $this->generateUrl('init_submit'),
            'method' => 'post',
        ]);

        return $this->render('Init/domain.html.twig', ['form' => $form->createView()]);
    }

    #[Route(path: '/init', name: 'init_submit', methods: ['POST'])]
    public function initSubmit(Request $request): Response
    {
        $form = $this->createForm(DomainCreateType::class, new DomainCreate());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->creator->create($form->getData()->domain);

            return $this->redirectToRoute('init_user');
        }

        return $this->render('Init/domain.html.twig', ['form' => $form->createView()]);
    }

    #[Route(path: '/init/user', name: 'init_user', methods: ['GET'])]
    public function user(Request $request): Response
    {
        // redirect if already configured
        if (0 < $this->manager->getRepository(User::class)->count([])) {
            return $this->redirectToRoute('index');
        }

        $form = $this->createForm(PlainPasswordType::class, new PlainPassword(), [
            'action' => $this->generateUrl('init_user'),
            'method' => 'post',
        ]);

        return $this->render('Init/user.html.twig', ['form' => $form->createView()]);
    }

    #[Route(path: '/init/user', name: 'init_user_submit', methods: ['POST'])]
    public function userSubmit(Request $request): Response
    {
        $form = $this->createForm(PlainPasswordType::class, new PlainPassword());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->updater->updateAdminPassword($form->getData()->getPlainPassword());

            $request->getSession()->getFlashBag()->add('success', 'flashes.password-change-successful');

            return $this->redirectToRoute('index');
        }

        return $this->render('Init/user.html.twig', ['form' => $form->createView()]);
    }
}
