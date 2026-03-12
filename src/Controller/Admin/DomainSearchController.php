<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class DomainSearchController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/admin/domains/search', name: 'admin_domain_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->query->getString('q', ''));

        $qb = $this->em->getRepository(Domain::class)
            ->createQueryBuilder('d')
            ->orderBy('d.name', 'ASC')
            ->setMaxResults(20);

        if ('' !== $query) {
            $qb->where('d.name LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }

        $domains = $qb->getQuery()->getResult();

        $results = array_map(static fn (Domain $domain): array => [
            'id' => $domain->getId(),
            'name' => $domain->getName(),
        ], $domains);

        return $this->json($results);
    }
}
