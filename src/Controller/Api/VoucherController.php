<?php

namespace App\Controller\Api;

use App\Enum\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Handler\VoucherHandler;
use App\Creator\VoucherCreator;
use App\Exception\ValidationException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class VoucherController extends AbstractController
{
    public function __construct(
        private readonly VoucherHandler $voucherHandler,
        private readonly VoucherCreator $voucherCreator,
    ) {
    }

    #[Route('/api/user/vouchers', name: 'get_user_voucher', methods: ['GET'], stateless: true)]
    public function getVouchers(
        #[CurrentUser] User $user
    ): JsonResponse {
        $vouchers = $this->voucherHandler->getVouchersByUser($user);
        $data = [];
        if ($vouchers) {
            foreach ($vouchers as $voucher) {
                array_push($data, $voucher->getCode());
            }
        }
        return $this->json([
            'status' => 'success',
            'vouchers' => $data
        ], 200);
    }

    #[Route('/api/user/vouchers', name: 'post_user_voucher', methods: ['POST'], stateless: true)]
    public function createVoucher(
        #[CurrentUser] User $user
    ): JsonResponse {
        if (!$this->isGranted(Roles::MULTIPLIER)) {
            return $this->json([
                'status' => 'error',
                'message' => 'forbidden'
            ], 403);
        }
        try {
            $voucher = $this->voucherCreator->create($user);
        } catch (ValidationException) {
            return $this->json([
                'status' => 'error',
                'message' => 'unknown error when creating voucher'
            ], 500);
        }
        return $this->json([
            'status' => 'success',
            'voucher' => $voucher->getCode()
        ], 200);
    }
}
