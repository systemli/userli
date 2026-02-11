<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use App\Form\Model\VoucherCheck;
use App\Form\VoucherCheckType;
use App\Handler\RegistrationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StartController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly RegistrationHandler $registrationHandler,
    ) {
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->isGranted(Roles::USER)) {
            return $this->redirectToRoute('start');
        }

        // forward to installer if no domains exist
        if (0 === $this->manager->getRepository(Domain::class)->count([])) {
            return $this->redirectToRoute('init');
        }

        return $this->render('Start/index_anonymous.html.twig', [
            'voucher_form' => $this->createForm(VoucherCheckType::class),
        ]);
    }

    #[Route(path: '/', name: 'check_voucher', methods: ['POST'])]
    public function checkVoucher(Request $request): Response
    {
        if (!$this->registrationHandler->isRegistrationOpen()) {
            return $this->redirectToRoute('index');
        }

        $form = $this->createForm(VoucherCheckType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var VoucherCheck $voucherCheck */
            $voucherCheck = $form->getData();

            return $this->redirectToRoute('register_voucher', ['voucher' => $voucherCheck->getVoucher()]);
        }

        return $this->render('Start/index_anonymous.html.twig', [
            'voucher_form' => $form,
        ]);
    }

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
