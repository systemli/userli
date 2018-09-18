<?php

namespace Tests\AppBundle\Guesser;

use AppBundle\Entity\Domain;
use AppBundle\Guesser\DomainGuesser;
use AppBundle\Repository\DomainRepository;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @author louis <louis@systemli.org>
 */
class DomainGuesserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DomainGuesser
     */
    private $guesser;

    public function testGuess()
    {
        $this->assertNull($this->guesser->guess('user@gmail.com'));
        $this->assertNotNull($this->guesser->guess('user@systemli.org'));
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
                if ($domain === 'systemli.org') {
                    $domain = new Domain();
                    $domain->setName('systemli.org');

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
