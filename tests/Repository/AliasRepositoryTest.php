<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\Filter\DomainFilter;
use App\Entity\User;
use App\Enum\Roles;
use App\Repository\AliasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AliasRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private AliasRepository $repository;
    private Domain $domainA;
    private Domain $domainB;

    #[Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(Alias::class);

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->domainA = new Domain();
        $this->domainA->setName('domain-a.org');
        $this->domainB = new Domain();
        $this->domainB->setName('domain-b.org');
        $this->em->persist($this->domainA);
        $this->em->persist($this->domainB);
        $this->em->flush();
    }

    public function testIsSourceOwnedByAdminSeesOwnersOfOtherDomains(): void
    {
        $admin = $this->createUser('admin@domain-a.org', $this->domainA, [Roles::ADMIN]);
        $user = $this->createUser('user@domain-a.org', $this->domainA, [Roles::USER]);
        $this->createAlias('admin-owned@domain-b.org', $admin);
        $this->createAlias('user-owned@domain-b.org', $user);
        $this->em->clear();
        $this->enableDomainFilter($this->domainB);

        self::assertTrue($this->repository->isSourceOwnedByAdmin('admin-owned@domain-b.org'));
        self::assertFalse($this->repository->isSourceOwnedByAdmin('user-owned@domain-b.org'));
        self::assertFalse($this->repository->isSourceOwnedByAdmin('unknown@domain-b.org'));
    }

    public function testIsSourceOwnedByAdminDoesNotLoadUsersOrLeakFilter(): void
    {
        $admin = $this->createUser('admin@domain-a.org', $this->domainA, [Roles::ADMIN]);
        $this->createAlias('admin-owned@domain-b.org', $admin);
        $this->em->clear();
        $this->enableDomainFilter($this->domainB);
        $domainId = $this->domainFilterId();

        $this->repository->isSourceOwnedByAdmin('admin-owned@domain-b.org');

        self::assertSame([], $this->em->getUnitOfWork()->getIdentityMap()[User::class] ?? []);
        self::assertNotNull($domainId);
        self::assertSame($domainId, $this->domainFilterId());
    }

    public function testFindByUserAcrossDomainsKeepsDomainFilterParameter(): void
    {
        $user = $this->createUser('user@domain-b.org', $this->domainB, [Roles::USER]);
        $this->enableDomainFilter($this->domainB);
        $domainId = $this->domainFilterId();

        $this->repository->findByUserAcrossDomains($user);

        self::assertNotNull($domainId);
        self::assertSame($domainId, $this->domainFilterId());
    }

    /**
     * @param string[] $roles
     */
    private function createUser(string $email, Domain $domain, array $roles): User
    {
        $user = new User($email);
        $user->setDomain($domain);
        $user->setRoles($roles);
        $user->setPassword('irrelevant');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createAlias(string $source, User $user): void
    {
        $alias = new Alias();
        $alias->setSource($source);
        $alias->setDestination($user->getEmail());
        $alias->setUser($user);
        $alias->setDomain($this->domainB);
        $this->em->persist($alias);
        $this->em->flush();
    }

    private function enableDomainFilter(Domain $domain): void
    {
        $this->em->getFilters()->enable('domain_filter')->setParameter('domainId', $domain->getId());
    }

    private function domainFilterId(): ?string
    {
        $filter = $this->em->getFilters()->getFilter('domain_filter');
        self::assertInstanceOf(DomainFilter::class, $filter);

        return $filter->getDomainId();
    }
}
