<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AppPasswordManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AppPasswordController extends AbstractController
{
    use DomainFilterTrait;

    public function __construct(
        private readonly AppPasswordManager $manager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/admin/app-passwords/', name: 'admin_app_password_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');
        [$domain, $domainId, $selectedDomainName] = $this->resolveDomainFilter($request, $this->em);

        return $this->render('Admin/AppPassword/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search, $domain),
            'search' => $search,
            'selectedDomain' => $domainId,
            'selectedDomainName' => $selectedDomainName,
        ]);
    }
}
