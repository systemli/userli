<?php

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StartController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
    )
    {
    }

    /**
     * @return Response
     */
    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        if ($this->isGranted(Roles::USER)) {
            return $this->redirectToRoute('start');
        }

        // forward to installer if no domains exist
        if (0 === $this->manager->getRepository(Domain::class)->count([])) {
            return $this->redirectToRoute('init');
        }

        return $this->render('Start/index_anonymous.html.twig');
    }

    /**
     * @return Response
     */
    #[Route(path: '/start', name: 'start')]
    public function start(): Response
    {
        if ($this->isGranted(Roles::SPAM)) {
            return $this->render('Start/index_spam.html.twig');
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render(
            'Start/index.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
            ]
        );
    }
}
