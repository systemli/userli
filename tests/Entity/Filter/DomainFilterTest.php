<?php

namespace App\Tests\Entity\Filter;

use App\Entity\Domain;
use App\Entity\Filter\DomainFilter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class DomainFilterTest.
 */
class DomainFilterTest extends TestCase
{
    private DomainFilter $filter;
    private EntityManager $manager;
    private ClassMetadata $targetEntity;

    public function setUp(): void
    {
        $filterCollection = $this->getMockBuilder(FilterCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        // return domainId
        $connection->method('quote')->willReturn(1);
        $this->manager->method('getFilters')->willReturn($filterCollection);
        $this->manager->method('getConnection')->willReturn($connection);
        $this->filter = $this->getMockBuilder(DomainFilter::class)
        ->disableOriginalConstructor()
        ->setMethodsExcept(['addFilterConstraint'])
        ->getMock();
        $this->filter->method('getDomainId')->willReturn('1');

        $this->targetEntity = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetDomainId(): void
    {
        $filter = new DomainFilter($this->manager);
        self::assertEquals(null, $filter->getDomainId());
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
