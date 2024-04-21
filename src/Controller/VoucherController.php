<?php

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
use Symfony\Component\Routing\Annotation\Route;

class VoucherController extends AbstractController
{
    public function __construct(
        private readonly VoucherHandler $voucherHandler,
        private readonly VoucherCreator $voucherCreator
    )
    {
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/voucher', name: 'vouchers')]
    public function voucher(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $voucherCreateForm = $this->createForm(
            VoucherCreateType::class,
            new VoucherCreate(),
            [
                'action' => $this->generateUrl('vouchers'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $voucherCreateForm->handleRequest($request);

            if ($voucherCreateForm->isSubmitted() && $voucherCreateForm->isValid()) {
                $this->createVoucher($request, $user);
            }
        }

        $vouchers = $this->voucherHandler->getVouchersByUser($user);

        return $this->render(
            'Start/vouchers.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'vouchers' => $vouchers,
                'voucher_form' => $voucherCreateForm->createView(),
            ]
        );
    }

    private function createVoucher(Request $request, User $user): void
    {
        if ($this->isGranted(Roles::MULTIPLIER)) {
            try {
                $this->voucherCreator->create($user);

                $request->getSession()->getFlashBag()->add('success', 'flashes.voucher-creation-successful');
            } catch (ValidationException) {
                // Should not throw
            }
        }
    }
}
