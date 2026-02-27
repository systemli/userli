<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Message\CreatePostmasterAlias;
use App\MessageHandler\CreatePostmasterAliasHandler;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CreatePostmasterAliasHandlerTest extends TestCase
{
    public function testCreatesPostmasterAliasForNonDefaultDomain(): void
    {
        $defaultDomain = new Domain();
        $defaultDomain->setName('default.org');

        $newDomain = new Domain();
        $newDomain->setName('new.org');

        $repository = $this->createMock(DomainRepository::class);
        $repository->method('find')->with(42)->willReturn($newDomain);
        $repository->method('getDefaultDomain')->willReturn($defaultDomain);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Domain::class)->willReturn($repository);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function ($alias) {
                return $alias instanceof Alias
                    && $alias->getSource() === 'postmaster@new.org'
                    && $alias->getDestination() === 'postmaster@default.org';
            }));
        $em->expects($this->once())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Created postmaster alias', ['domain' => 'new.org']);

        $handler = new CreatePostmasterAliasHandler($em, $repository, $logger);
        $handler(new CreatePostmasterAlias(42));
    }

    public function testSkipsPostmasterAliasForDefaultDomain(): void
    {
        $defaultDomain = new Domain();
        $defaultDomain->setName('default.org');

        $repository = $this->createMock(DomainRepository::class);
        $repository->method('find')->with(1)->willReturn($defaultDomain);
        $repository->method('getDefaultDomain')->willReturn($defaultDomain);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Domain::class)->willReturn($repository);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $logger = $this->createStub(LoggerInterface::class);

        $handler = new CreatePostmasterAliasHandler($em, $repository, $logger);
        $handler(new CreatePostmasterAlias(1));
    }

    public function testSkipsWhenDomainNotFound(): void
    {
        $repository = $this->createMock(DomainRepository::class);
        $repository->method('find')->with(999)->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Domain::class)->willReturn($repository);
        $em->expects($this->never())->method('persist');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Domain not found for postmaster alias creation', ['domainId' => 999]);

        $handler = new CreatePostmasterAliasHandler($em, $repository, $logger);
        $handler(new CreatePostmasterAlias(999));
    }
}
