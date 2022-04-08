<?php

namespace App\Tests\Guesser;

use App\Entity\Domain;
use App\Guesser\DomainGuesser;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DomainGuesserTest extends TestCase
{
    /**
     * @var DomainGuesser
     */
    private $guesser;

    public function testGuess(): void
    {
        $this->assertNull($this->guesser->guess('user@gmail.com'));
        $this->assertNotNull($this->guesser->guess('user@example.org'));
    }

    protected function setUp(): void
    {
        $this->guesser = new DomainGuesser($this->getManager());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    private function getManager(): EntityManagerInterface
    {
        $repository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->method('findByName')->willReturnCallback(
            function ($domain) {
                if ('example.org' === $domain) {
                    $domain = new Domain();
                    $domain->setName('example.org');

                    return $domain;
                }

                return null;
            }
        );

        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
