<?php

declare(strict_types=1);

namespace App\Tests\Entity\Filter;

use App\Entity\Domain;
use App\Entity\Filter\DomainFilter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * Class DomainFilterTest.
 */
class DomainFilterTest extends TestCase
{
    private DomainFilter $filter;
    private EntityManagerInterface $manager;
    private Stub $targetEntity;

    protected function setUp(): void
    {
        $filterCollection = $this->createStub(FilterCollection::class);
        $connection = $this->createStub(Connection::class);
        $this->manager = $this->createStub(EntityManagerInterface::class);
        // return domainId
        $connection->method('quote')->willReturn('1');
        $this->manager->method('getFilters')->willReturn($filterCollection);
        $this->manager->method('getConnection')->willReturn($connection);
        $this->filter = new DomainFilter($this->manager);
        $this->filter->setParameter('domainId', '1');

        $this->targetEntity = $this->createStub(ClassMetadata::class);
    }

    public function testGetDomainId(): void
    {
        $filter = new DomainFilter($this->manager);
        self::assertNull($filter->getDomainId());
        self::assertNotEquals(1, $filter->getDomainId());

        $filter->setParameter('domainId', '1');
        self::assertEquals('1', $filter->getParameter('domainId'));
        self::assertEquals('1', $filter->getDomainId());
        self::assertNotEquals('2', $filter->getDomainId());
    }

    public function testMatchDomain(): void
    {
        $this->targetEntity->method('getName')->willReturn(Domain::class);
        $this->targetEntity->method('getAssociationMappings')->willReturn([]);

        self::assertEquals(
            'domain.id = 1',
            $this->filter->addFilterConstraint($this->targetEntity, 'domain')
        );
    }

    public function testDomainAware(): void
    {
        $this->targetEntity->method('getName')->willReturn('xyz');
        $this->targetEntity->method('getAssociationMappings')->willReturn([
            'domain' => 1,
            'other' => 2, ]
        );

        self::assertEquals(
            'xyz.domain_id = 1',
            $this->filter->addFilterConstraint($this->targetEntity, 'xyz')
        );
    }

    public function testNotDomainAndNotDomainAware(): void
    {
        $this->targetEntity->method('getName')->willReturn('xyz');
        $this->targetEntity->method('getAssociationMappings')->willReturn([
            'user' => 1,
            'other' => 2, ]
        );

        self::assertEquals('', $this->filter->addFilterConstraint($this->targetEntity, 'xyz')
        );
    }
}
