<?php

declare(strict_types=1);

namespace App\Tests\Guesser;

use App\Entity\Domain;
use App\Guesser\DomainGuesser;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DomainGuesserTest extends TestCase
{
    private DomainGuesser $guesser;

    public function testGuess(): void
    {
        self::assertNull($this->guesser->guess('user@gmail.com'));
        self::assertNotNull($this->guesser->guess('user@example.org'));
    }

    protected function setUp(): void
    {
        $this->guesser = new DomainGuesser($this->getManager());
    }

    private function getManager(): EntityManagerInterface
    {
        $repository = $this->createStub(DomainRepository::class);

        $repository->method('findByName')->willReturnCallback(
            static function ($domain) {
                if ('example.org' === $domain) {
                    $domain = new Domain();
                    $domain->setName('example.org');

                    return $domain;
                }

                return null;
            }
        );

        $manager = $this->createStub(EntityManagerInterface::class);

        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
