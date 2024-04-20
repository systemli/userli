<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Alias;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class ApiQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if (null === $this->security->getUser()) {
            return;
        }
        $this->filterEntity($queryBuilder, $resourceClass, $this->security->getUser());
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        // Same filters as for collections
        $this->applyToCollection($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }

    private function filterEntity(QueryBuilder $queryBuilder, string $resourceClass, User $user): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        if (User::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf('%s.id = :current_user', $rootAlias));
            $queryBuilder->setParameter('current_user', $user->getId());
        } else if (
            (OpenPgpKey::class === $resourceClass) ||
            (Alias::class === $resourceClass) ||
            (Voucher::class === $resourceClass)
        ) {
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
            $queryBuilder->setParameter('current_user', $user->getId());
        }
        // additional contraints for vouchers
        if (Voucher::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf('%s.redeemedTime is NULL', $rootAlias));
        }
        // additional constraints for aliases
        if (Alias::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf('%s.deleted = false', $rootAlias));
        }
    }
}
