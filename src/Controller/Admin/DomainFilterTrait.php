<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

trait DomainFilterTrait
{
    /**
     * @return array{0: ?Domain, 1: int, 2: string} domain, selected domain ID and name for the domain filter
     */
    private function resolveDomainFilter(Request $request, EntityManagerInterface $em): array
    {
        $domainId = $request->query->getInt('domain', 0);
        $domain = $domainId > 0 ? $em->getRepository(Domain::class)->find($domainId) : null;

        return [$domain, $domainId, $domain?->getName() ?? ''];
    }
}
