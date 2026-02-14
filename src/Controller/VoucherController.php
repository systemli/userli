<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Form\Model\VoucherCreate;
use App\Form\VoucherCreateType;
use App\Service\VoucherManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VoucherController extends AbstractController
{
    public function __construct(
        private readonly VoucherManager $voucherManager,
    ) {
    }

    #[Route(path: '/account/voucher', name: 'vouchers', methods: ['GET'])]
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

        $vouchers = $this->voucherManager->getVouchersByUser($user);

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

    #[Route(path: '/account/voucher/create', name: 'vouchers_create', methods: ['POST'])]
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
                $this->voucherManager->create($user, $user->getDomain());
                $this->addFlash('success', 'flashes.voucher-creation-successful');
            } catch (ValidationException) {
                // Should not throw
            }
        }
    }
}
