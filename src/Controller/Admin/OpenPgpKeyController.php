<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\Roles;
use App\Service\OpenPgpKeyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Roles::ADMIN)]
final class OpenPgpKeyController extends AbstractController
{
    public function __construct(
        private readonly OpenPgpKeyManager $manager,
    ) {
    }

    #[Route('/admin/openpgp-keys/', name: 'admin_openpgp_key_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');

        return $this->render('Admin/OpenPgpKey/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search),
            'search' => $search,
        ]);
    }
}
