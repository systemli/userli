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
 *
 * @author tim <tim@systemli.org>
 */
class DomainFilterTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $filter;
    /**
     * @var MockObject
     */
    private $manager;
    /**
     * @var MockObject
     */
    private $targetEntity;

    public function setUp()
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
        $this->filter->method('getDomainId')->willReturn(1);

        $this->targetEntity = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetDomainId()
    {
        $filter = new DomainFilter($this->manager);
        $this->assertEquals(null, $filter->getDomainId());
        $this->assertNotEquals(1, $filter->getDomainId());

        $filter->setParameter('domainId', '1');
        $this->assertEquals('1', $filter->getParameter('domainId'));
        $this->assertEquals('1', $filter->getDomainId());
        $this->assertNotEquals('2', $filter->getDomainId());
    }

    public function testMatchDomain()
    {
        $this->targetEntity->method('getName')->willReturn(Domain::class);
        $this->targetEntity->method('getAssociationMappings')->willReturn([]);

        $this->assertEquals(
            'domain.id = 1',
            $this->filter->addFilterConstraint($this->targetEntity, 'domain')
        );
    }

    public function testDomainAware()
    {
        $this->targetEntity->method('getName')->willReturn('xyz');
        $this->targetEntity->method('getAssociationMappings')->willReturn([
                'domain' => 1,
                'other' => 2, ]
        );

        $this->assertEquals(
            'xyz.domain_id = 1',
            $this->filter->addFilterConstraint($this->targetEntity, 'xyz')
        );
    }

    public function testNotDomainAndNotDomainAware()
    {
        $this->targetEntity->method('getName')->willReturn('xyz');
        $this->targetEntity->method('getAssociationMappings')->willReturn([
                'user' => 1,
                'other' => 2, ]
        );

        $this->assertEquals('', $this->filter->addFilterConstraint($this->targetEntity, 'xyz')
        );
    }
}
