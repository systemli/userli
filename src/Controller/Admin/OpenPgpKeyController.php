<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\OpenPgpKeyManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpenPgpKeyController extends AbstractController
{
    use DomainFilterTrait;

    public function __construct(
        private readonly OpenPgpKeyManager $manager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/admin/openpgp-keys/', name: 'admin_openpgp_key_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');
        [$domain, $domainId, $selectedDomainName] = $this->resolveDomainFilter($request, $this->em);

        return $this->render('Admin/OpenPgpKey/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search, $domain),
            'search' => $search,
            'selectedDomain' => $domainId,
            'selectedDomainName' => $selectedDomainName,
        ]);
    }
}
