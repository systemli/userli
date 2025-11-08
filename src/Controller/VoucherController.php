<?php

declare(strict_types=1);

namespace App\Controller;

use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Form\Model\VoucherCreate;
use App\Form\VoucherCreateType;
use App\Handler\VoucherHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VoucherController extends AbstractController
{
    public function __construct(
        private readonly VoucherHandler $voucherHandler,
        private readonly VoucherCreator $voucherCreator,
    ) {
    }

    #[Route(path: '/voucher', name: 'vouchers', methods: ['GET'])]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $voucherCreateForm = $this->createForm(
            VoucherCreateType::class,
            new VoucherCreate(),
            [
                'action' => $this->generateUrl('vouchers_create'),
                'method' => 'post',
            ]
        );

        $vouchers = $this->voucherHandler->getVouchersByUser($user);

        return $this->render(
            'Voucher/show.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'vouchers' => $vouchers,
                'voucher_form' => $voucherCreateForm,
            ]
        );
    }

    #[Route(path: '/voucher/create', name: 'vouchers_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $voucherCreateForm = $this->createForm(VoucherCreateType::class, new VoucherCreate());
        $voucherCreateForm->handleRequest($request);

        if ($voucherCreateForm->isSubmitted() && $voucherCreateForm->isValid()) {
            $this->processVoucherCreation($user);
        }

        return $this->redirectToRoute('vouchers');
    }

    private function processVoucherCreation(User $user): void
    {
        if ($this->isGranted(Roles::MULTIPLIER)) {
            try {
                $this->voucherCreator->create($user);
                $this->addFlash('success', 'flashes.voucher-creation-successful');
            } catch (ValidationException) {
                // Should not throw
            }
        }
    }
}
