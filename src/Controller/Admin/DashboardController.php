<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\Roles;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\OpenPgpKeyRepository;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly DomainRepository $domainRepository,
        private readonly AliasRepository $aliasRepository,
        private readonly VoucherRepository $voucherRepository,
        private readonly OpenPgpKeyRepository $openPgpKeyRepository,
    ) {
    }

    #[Route('/admin/', name: 'admin_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $stats = [
            'users' => $this->userRepository->countUsers(),
            'aliases' => $this->aliasRepository->countByFilters(),
            'openpgp_keys' => $this->openPgpKeyRepository->countKeys(),
        ];

        if ($this->isGranted(Roles::ADMIN)) {
            $stats['domains'] = $this->domainRepository->countBySearch();
            $stats['vouchers_redeemed'] = $this->voucherRepository->countRedeemedVouchers();
            $stats['vouchers_unredeemed'] = $this->voucherRepository->countUnredeemedVouchers();
        }

        return $this->render('Admin/Dashboard/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}
