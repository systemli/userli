<?php

namespace App\Repository;

use DateTimeImmutable;
use App\Entity\ApiToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiToken>
 */
class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function updateLastUsedTime(ApiToken $token): void
    {
        $token->setLastUsedTime(new DateTimeImmutable());
        $this->_em->flush();
    }
}
