<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Alias;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class ApiQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        // No further constraints for admins or userless requests
        if ($this->security->isGranted('ROLE_ADMIN')
            || null === $user = $this->security->getUser()) {
            return;
        }

        $this->filterOpenPgpUser($queryBuilder, $resourceClass, $user);

        if ($this->security->isGranted('ROLE_DOMAIN_ADMIN')) {
            // Filter for own domain
            $this->filterDomainAdmin($queryBuilder, $resourceClass, $user);
        } else {
            // Filter for own user
            $this->filterUser($queryBuilder, $resourceClass, $user);
        }
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        // Same filters as for collections
        $this->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }

    private function filterOpenPgpUser(QueryBuilder $queryBuilder, string $resourceClass, User $user): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (OpenPgpKey::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
            $queryBuilder->setParameter('current_user', $user->getId());
        }
    }

    private function filterDomainAdmin(QueryBuilder $queryBuilder, string $resourceClass, User $user): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (Alias::class === $resourceClass || User::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf('%s.domain = :current_user_domain', $rootAlias));
            $queryBuilder->setParameter('current_user_domain', $user->getDomain()->getId());
        }
    }

    private function filterUser(QueryBuilder $queryBuilder, string $resourceClass, User $user): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (Alias::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
            $queryBuilder->setParameter('current_user', $user->getId());
        } elseif (User::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf('%s.id = :current_user', $rootAlias));
            $queryBuilder->setParameter('current_user', $user->getId());
        }
    }
}
