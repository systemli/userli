<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Domain;
use App\Enum\Roles;
use App\Form\LoginType;
use App\Form\Model\VoucherCheck;
use App\Form\VoucherCheckType;
use App\Handler\RegistrationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class StartController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly RegistrationHandler $registrationHandler,
        private readonly AuthenticationUtils $authenticationUtils,
    ) {
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->isGranted(Roles::USER)) {
            return $this->redirectToRoute('account');
        }

        // forward to installer if no domains exist
        if (0 === $this->manager->getRepository(Domain::class)->count([])) {
            return $this->redirectToRoute('init');
        }

        return $this->render('Start/index_anonymous.html.twig', [
            'voucher_form' => $this->createForm(VoucherCheckType::class),
            'login_form' => $this->createForm(LoginType::class, null, [
                'last_username' => $this->authenticationUtils->getLastUsername(),
                'action' => $this->generateUrl('login'),
            ]),
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
            'login_form' => $this->createForm(LoginType::class, null, [
                'last_username' => $this->authenticationUtils->getLastUsername(),
                'action' => $this->generateUrl('login'),
            ]),
        ]);
    }
}
