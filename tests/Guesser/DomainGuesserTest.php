<?php

namespace App\Tests\Guesser;

use App\Entity\Domain;
use App\Guesser\DomainGuesser;
use App\Repository\DomainRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @author louis <louis@systemli.org>
 */
class DomainGuesserTest extends TestCase
{
    /**
     * @var DomainGuesser
     */
    private $guesser;

    public function testGuess()
    {
        $this->assertNull($this->guesser->guess('user@gmail.com'));
        $this->assertNotNull($this->guesser->guess('user@example.org'));
    }

    protected function setUp()
    {
        $this->guesser = new DomainGuesser($this->getManager());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    private function getManager()
    {
        $repository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())->method('findByName')->willReturnCallback(
            function ($domain) {
                if ('example.org' === $domain) {
                    $domain = new Domain();
                    $domain->setName('example.org');

                    return $domain;
                }

                return null;
            }
        );

        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
