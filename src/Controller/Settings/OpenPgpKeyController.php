<?php

declare(strict_types=1);

namespace App\Controller\Settings;

use App\Service\OpenPgpKeyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpenPgpKeyController extends AbstractController
{
    public function __construct(
        private readonly OpenPgpKeyManager $manager,
    ) {
    }

    #[Route('/settings/openpgp-keys/', name: 'settings_openpgp_key_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');

        return $this->render('Settings/OpenPgpKey/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search),
            'search' => $search,
        ]);
    }
}
