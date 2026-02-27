<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\Domain;
use App\Entity\WebhookEndpoint;
use App\EntityListener\DisableWebhookEndpointOnDomainRemovalListener;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DisableWebhookEndpointOnDomainRemovalListenerTest extends TestCase
{
    public function testDisablesEndpointWithSingleDomain(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $endpoint = new WebhookEndpoint('https://example.org/hook', 'secret');
        $endpoint->setEnabled(true);
        $endpoint->addDomain($domain);

        $listener = new DisableWebhookEndpointOnDomainRemovalListener(
            $this->createEntityManager([$endpoint]),
        );

        $listener->preRemove($domain);

        self::assertFalse($endpoint->isEnabled());
    }

    public function testDoesNotDisableEndpointWithMultipleDomains(): void
    {
        $domain1 = new Domain();
        $domain1->setName('example.org');

        $domain2 = new Domain();
        $domain2->setName('other.org');

        $endpoint = new WebhookEndpoint('https://example.org/hook', 'secret');
        $endpoint->setEnabled(true);
        $endpoint->addDomain($domain1);
        $endpoint->addDomain($domain2);

        $listener = new DisableWebhookEndpointOnDomainRemovalListener(
            $this->createEntityManager([$endpoint]),
        );

        $listener->preRemove($domain1);

        self::assertTrue($endpoint->isEnabled());
    }

    public function testNoEndpointsAffected(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $listener = new DisableWebhookEndpointOnDomainRemovalListener(
            $this->createEntityManager([]),
        );

        $listener->preRemove($domain);

        // No exception thrown — nothing to disable
        $this->addToAssertionCount(1);
    }

    private function createEntityManager(array $endpoints): EntityManager
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([$em])
            ->onlyMethods(['execute', 'getResult', 'getSql', 'setFirstResult', 'setMaxResults'])
            ->getMock();
        $query->method('getResult')->willReturn($endpoints);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$em])
            ->onlyMethods(['innerJoin', 'where', 'setParameter', 'getQuery'])
            ->getMock();
        $queryBuilder->method('innerJoin')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        $em->method('getRepository')->willReturn($repository);

        return $em;
    }
}
