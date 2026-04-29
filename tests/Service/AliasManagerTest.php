<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\PaginatedResult;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Form\Model\AliasAdminModel;
use App\Handler\DeleteHandler;
use App\Repository\AliasRepository;
use App\Service\AliasManager;
use App\Service\DomainGuesser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AliasManagerTest extends TestCase
{
    private AliasRepository&Stub $repository;
    private EntityManagerInterface&Stub $entityManager;
    private DomainGuesser&Stub $domainGuesser;
    private DeleteHandler&Stub $deleteHandler;
    private Security&Stub $security;
    private ValidatorInterface&Stub $validator;
    private EventDispatcherInterface&Stub $eventDispatcher;
    private AliasManager $manager;
    private Domain $domain;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(AliasRepository::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->domainGuesser = $this->createStub(DomainGuesser::class);
        $this->deleteHandler = $this->createStub(DeleteHandler::class);
        $this->security = $this->createStub(Security::class);
        $this->validator = $this->createStub(ValidatorInterface::class);
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
        $this->eventDispatcher = $this->createStub(EventDispatcherInterface::class);

        $this->domain = new Domain();
        $this->domain->setName('example.org');

        $this->security->method('isGranted')->willReturn(true);
        $this->security->method('getUser')->willReturn(new User('admin@example.org'));
        $this->domainGuesser->method('guess')->willReturn($this->domain);

        $this->manager = new AliasManager(
            $this->entityManager,
            $this->repository,
            $this->domainGuesser,
            $this->deleteHandler,
            $this->security,
            $this->validator,
            $this->eventDispatcher,
        );
    }

    public function testFindPaginated(): void
    {
        $this->repository->method('countByFilters')->willReturn(25);
        $this->repository->method('findPaginatedByFilters')->willReturn([
            new Alias(),
            new Alias(),
        ]);

        $result = $this->manager->findPaginated(1, '', null, 'active');

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertSame(1, $result->page);
        self::assertSame(2, $result->totalPages);
        self::assertSame(25, $result->total);
        self::assertCount(2, $result->items);
    }

    public function testFindPaginatedWithFilters(): void
    {
        $this->repository->method('countByFilters')->willReturn(5);
        $this->repository->method('findPaginatedByFilters')->willReturn([
            new Alias(),
        ]);

        $result = $this->manager->findPaginated(1, 'test', null, 'deleted');

        self::assertSame(1, $result->page);
        self::assertSame(1, $result->totalPages);
        self::assertSame(5, $result->total);
        self::assertCount(1, $result->items);
    }

    public function testFindPaginatedClampsPageToMin1(): void
    {
        $this->repository->method('countByFilters')->willReturn(0);
        $this->repository->method('findPaginatedByFilters')->willReturn([]);

        $result = $this->manager->findPaginated(0);

        self::assertSame(1, $result->page);
        self::assertSame(1, $result->totalPages);
    }

    public function testCreate(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Alias::class));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $manager = new AliasManager(
            $entityManager,
            $this->repository,
            $this->domainGuesser,
            $this->deleteHandler,
            $this->security,
            $this->validator,
            $this->eventDispatcher,
        );

        $model = new AliasAdminModel();
        $model->setSource('alias@example.org');
        $model->setDestination('user@example.org');

        $alias = $manager->create($model);

        self::assertInstanceOf(Alias::class, $alias);
        self::assertSame('alias@example.org', $alias->getSource());
        self::assertSame('user@example.org', $alias->getDestination());
        self::assertSame($this->domain, $alias->getDomain());
    }

    public function testCreateWithValidationException(): void
    {
        $violation = new ConstraintViolation('message', 'messageTemplate', [], null, null, 'someValue');

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $manager = new AliasManager(
            $this->entityManager,
            $this->repository,
            $this->domainGuesser,
            $this->deleteHandler,
            $this->security,
            $validator,
            $this->eventDispatcher,
        );

        $model = new AliasAdminModel();
        $model->setSource('alias@example.org');

        $this->expectException(ValidationException::class);

        $manager->create($model);
    }

    public function testCreateSetsDefaultUserWhenNoUserAndNoDestination(): void
    {
        $currentUser = new User('admin@example.org');
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(true);
        $security->method('getUser')->willReturn($currentUser);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $manager = new AliasManager(
            $entityManager,
            $this->repository,
            $this->domainGuesser,
            $this->deleteHandler,
            $security,
            $this->validator,
            $this->eventDispatcher,
        );

        $model = new AliasAdminModel();
        $model->setSource('alias@example.org');

        $alias = $manager->create($model);

        self::assertSame($currentUser, $alias->getUser());
        self::assertSame('admin@example.org', $alias->getDestination());
    }

    public function testCreateSetsDestinationFromUser(): void
    {
        $user = new User('target@example.org');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $manager = new AliasManager(
            $entityManager,
            $this->repository,
            $this->domainGuesser,
            $this->deleteHandler,
            $this->security,
            $this->validator,
            $this->eventDispatcher,
        );

        $model = new AliasAdminModel();
        $model->setSource('alias@example.org');
        $model->setUser($user);

        $alias = $manager->create($model);

        self::assertSame($user, $alias->getUser());
        self::assertSame('target@example.org', $alias->getDestination());
    }

    public function testUpdate(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $manager = new AliasManager(
            $entityManager,
            $this->repository,
            $this->domainGuesser,
            $this->deleteHandler,
            $this->security,
            $this->validator,
            $this->eventDispatcher,
        );

        $alias = new Alias();
        $alias->setSource('alias@example.org');

        $user = new User('user@example.org');
        $model = AliasAdminModel::fromAlias($alias);
        $model->setUser($user);
        $model->setDestination('dest@example.org');

        $manager->update($alias, $model);

        self::assertSame($user, $alias->getUser());
        self::assertSame('dest@example.org', $alias->getDestination());
    }

    public function testDelete(): void
    {
        $alias = new Alias();

        /** @var DeleteHandler&MockObject $deleteHandler */
        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler
            ->expects($this->once())
            ->method('deleteAlias')
            ->with($alias);

        $manager = new AliasManager(
            $this->entityManager,
            $this->repository,
            $this->domainGuesser,
            $deleteHandler,
            $this->security,
            $this->validator,
            $this->eventDispatcher,
        );

        $manager->delete($alias);
    }

    public function testDomainAdminForcesDestinationToUserEmail(): void
    {
        $user = new User('user@example.org');

        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(false); // Not ROLE_ADMIN
        $security->method('getUser')->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $manager = new AliasManager(
            $entityManager,
            $this->repository,
            $this->domainGuesser,
            $this->deleteHandler,
            $security,
            $this->validator,
            $this->eventDispatcher,
        );

        $model = new AliasAdminModel();
        $model->setSource('alias@example.org');
        $model->setUser($user);
        $model->setDestination('other@example.org');

        $alias = $manager->create($model);

        // Domain admin should have destination forced to user's email
        self::assertSame('user@example.org', $alias->getDestination());
    }

    private function createUser(): User
    {
        $domain = new Domain();
        $domain->setName('example.org');
        $user = new User('user@example.org');
        $user->setDomain($domain);

        return $user;
    }

    public function testCreateForUserCustomAlias(): void
    {
        $user = $this->createUser();
        $this->repository->method('findByUser')->willReturn([]);

        $alias = $this->manager->createForUser($user, 'myalias');

        self::assertInstanceOf(Alias::class, $alias);
        self::assertSame('myalias@example.org', $alias->getSource());
        self::assertFalse($alias->isRandom());
        self::assertSame('user@example.org', $alias->getDestination());
    }

    public function testCreateForUserRandomAlias(): void
    {
        $user = $this->createUser();
        $this->repository->method('findByUser')->willReturn([]);

        $alias = $this->manager->createForUser($user);

        self::assertInstanceOf(Alias::class, $alias);
        self::assertTrue($alias->isRandom());
        self::assertSame(Alias::RANDOM_ALIAS_LENGTH + 1 + strlen('example.org'), strlen($alias->getSource()));
    }

    public function testCreateForUserReturnsNullWhenLimitReached(): void
    {
        $user = $this->createUser();
        $aliases = array_fill(0, AliasManager::ALIAS_LIMIT_CUSTOM, new Alias());
        $this->repository->method('findByUser')->willReturn($aliases);

        $result = $this->manager->createForUser($user, 'myalias');

        self::assertNull($result);
    }

    public function testCreateForUserRandomRetriesOnCollision(): void
    {
        $user = $this->createUser();
        $this->repository->method('findByUser')->willReturn([]);

        $violation = new ConstraintViolation('message', 'messageTemplate', [], null, null, 'someValue');

        $callCount = 0;
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturnCallback(
            static function () use (&$callCount, $violation): ConstraintViolationList {
                ++$callCount;
                if (1 === $callCount) {
                    return new ConstraintViolationList([$violation]);
                }

                return new ConstraintViolationList();
            }
        );

        $manager = new AliasManager(
            $this->entityManager,
            $this->repository,
            $this->domainGuesser,
            $this->deleteHandler,
            $this->security,
            $validator,
            $this->eventDispatcher,
        );

        $alias = $manager->createForUser($user);

        self::assertInstanceOf(Alias::class, $alias);
        self::assertEquals(2, $callCount);
    }

    public function testCheckAliasLimit(): void
    {
        self::assertTrue($this->manager->checkAliasLimit([], false));
        self::assertTrue($this->manager->checkAliasLimit([], true));
        self::assertFalse($this->manager->checkAliasLimit(array_fill(0, AliasManager::ALIAS_LIMIT_CUSTOM, new Alias()), false));
        self::assertFalse($this->manager->checkAliasLimit(array_fill(0, AliasManager::ALIAS_LIMIT_RANDOM, new Alias()), true));
    }
}
