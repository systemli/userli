<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\PaginatedResult;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use App\Form\Model\UserAdminModel;
use App\Handler\DeleteHandler;
use App\Handler\MailCryptKeyHandler;
use App\Helper\PasswordUpdater;
use App\Repository\AliasRepository;
use App\Repository\OpenPgpKeyRepository;
use App\Repository\UserNotificationRepository;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use App\Service\DomainGuesser;
use App\Service\SettingsService;
use App\Service\UserManager;
use App\Service\UserResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserRepository&Stub $repository;
    private EntityManagerInterface&Stub $entityManager;
    private PasswordUpdater&Stub $passwordUpdater;
    private MailCryptKeyHandler&Stub $mailCryptKeyHandler;
    private SettingsService&Stub $settingsService;
    private DomainGuesser&Stub $domainGuesser;
    private UserResetService&Stub $userResetService;
    private DeleteHandler&Stub $deleteHandler;
    private AliasRepository&Stub $aliasRepository;
    private VoucherRepository&Stub $voucherRepository;
    private OpenPgpKeyRepository&Stub $openPgpKeyRepository;
    private UserNotificationRepository&Stub $userNotificationRepository;
    private UserManager $manager;
    private Domain $domain;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(UserRepository::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->passwordUpdater = $this->createStub(PasswordUpdater::class);
        $this->mailCryptKeyHandler = $this->createStub(MailCryptKeyHandler::class);
        $this->settingsService = $this->createStub(SettingsService::class);
        $this->domainGuesser = $this->createStub(DomainGuesser::class);
        $this->userResetService = $this->createStub(UserResetService::class);
        $this->deleteHandler = $this->createStub(DeleteHandler::class);
        $this->aliasRepository = $this->createStub(AliasRepository::class);
        $this->voucherRepository = $this->createStub(VoucherRepository::class);
        $this->openPgpKeyRepository = $this->createStub(OpenPgpKeyRepository::class);
        $this->userNotificationRepository = $this->createStub(UserNotificationRepository::class);

        $this->domain = new Domain();
        $this->domain->setName('example.org');

        $this->settingsService->method('get')->willReturn(0);
        $this->domainGuesser->method('guess')->willReturn($this->domain);

        $this->manager = new UserManager(
            $this->entityManager,
            $this->repository,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->settingsService,
            $this->domainGuesser,
            $this->userResetService,
            $this->deleteHandler,
            $this->aliasRepository,
            $this->voucherRepository,
            $this->openPgpKeyRepository,
            $this->userNotificationRepository,
        );
    }

    public function testFindPaginated(): void
    {
        $this->repository->method('countByFilters')->willReturn(25);
        $this->repository->method('findPaginatedByFilters')->willReturn([
            new User('user1@example.org'),
            new User('user2@example.org'),
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
            new User('user@example.org'),
        ]);

        $result = $this->manager->findPaginated(1, 'test', null, 'deleted', 'ROLE_ADMIN', 'enabled', 'enabled');

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
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $entityManager->expects($this->once())->method('flush');

        /** @var PasswordUpdater&MockObject $passwordUpdater */
        $passwordUpdater = $this->createMock(PasswordUpdater::class);
        $passwordUpdater->expects($this->once())->method('updatePassword');

        /** @var MailCryptKeyHandler&MockObject $mailCryptKeyHandler */
        $mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);
        $mailCryptKeyHandler->expects($this->once())->method('create');

        $manager = new UserManager(
            $entityManager,
            $this->repository,
            $passwordUpdater,
            $mailCryptKeyHandler,
            $this->settingsService,
            $this->domainGuesser,
            $this->userResetService,
            $this->deleteHandler,
            $this->aliasRepository,
            $this->voucherRepository,
            $this->openPgpKeyRepository,
            $this->userNotificationRepository,
        );

        $model = new UserAdminModel();
        $model->setEmail('newuser@example.org');
        $model->setPlainPassword('securePassword123');
        $model->setRoles([Roles::USER]);
        $model->setQuota(1024);

        $user = $manager->create($model);

        self::assertInstanceOf(User::class, $user);
        self::assertSame('newuser@example.org', $user->getEmail());
        self::assertContains(Roles::USER, $user->getRoles());
        self::assertSame(1024, $user->getQuota());
        self::assertTrue($user->isPasswordChangeRequired());
        self::assertSame($this->domain, $user->getDomain());
    }

    public function testCreateAddsRoleUserIfMissing(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $manager = new UserManager(
            $entityManager,
            $this->repository,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->settingsService,
            $this->domainGuesser,
            $this->userResetService,
            $this->deleteHandler,
            $this->aliasRepository,
            $this->voucherRepository,
            $this->openPgpKeyRepository,
            $this->userNotificationRepository,
        );

        $model = new UserAdminModel();
        $model->setEmail('admin@example.org');
        $model->setPlainPassword('securePassword123');
        $model->setRoles([Roles::ADMIN]);

        $user = $manager->create($model);

        self::assertContains(Roles::USER, $user->getRoles());
        self::assertContains(Roles::ADMIN, $user->getRoles());
    }

    public function testCreateWithMailCryptEnforced(): void
    {
        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturn(2);

        /** @var MailCryptKeyHandler&MockObject $mailCryptKeyHandler */
        $mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);
        $mailCryptKeyHandler->expects($this->once())->method('create')
            ->with($this->isInstanceOf(User::class), 'securePassword123', true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $manager = new UserManager(
            $entityManager,
            $this->repository,
            $this->passwordUpdater,
            $mailCryptKeyHandler,
            $settingsService,
            $this->domainGuesser,
            $this->userResetService,
            $this->deleteHandler,
            $this->aliasRepository,
            $this->voucherRepository,
            $this->openPgpKeyRepository,
            $this->userNotificationRepository,
        );

        $model = new UserAdminModel();
        $model->setEmail('user@example.org');
        $model->setPlainPassword('securePassword123');
        $model->setRoles([Roles::USER]);

        $manager->create($model);
    }

    public function testUpdateWithoutPasswordChange(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $manager = new UserManager(
            $entityManager,
            $this->repository,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->settingsService,
            $this->domainGuesser,
            $this->userResetService,
            $this->deleteHandler,
            $this->aliasRepository,
            $this->voucherRepository,
            $this->openPgpKeyRepository,
            $this->userNotificationRepository,
        );

        $user = new User('user@example.org');

        $model = new UserAdminModel();
        $model->setRoles([Roles::USER, Roles::ADMIN]);
        $model->setQuota(2048);
        $model->setTotpConfirmed(false);
        $model->setPasswordChangeRequired(false);

        $recoveryToken = $manager->update($user, $model);

        self::assertNull($recoveryToken);
        self::assertSame([Roles::USER, Roles::ADMIN], $user->getRoles());
        self::assertSame(2048, $user->getQuota());
        self::assertFalse($user->isPasswordChangeRequired());
    }

    public function testUpdateWithPasswordChangeAndMailCrypt(): void
    {
        /** @var UserResetService&MockObject $userResetService */
        $userResetService = $this->createMock(UserResetService::class);
        $userResetService->expects($this->once())->method('resetUser')
            ->with($this->isInstanceOf(User::class), 'newPassword123')
            ->willReturn('recovery-token-abc123');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $manager = new UserManager(
            $entityManager,
            $this->repository,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->settingsService,
            $this->domainGuesser,
            $userResetService,
            $this->deleteHandler,
            $this->aliasRepository,
            $this->voucherRepository,
            $this->openPgpKeyRepository,
            $this->userNotificationRepository,
        );

        $user = new User('user@example.org');
        $user->setMailCryptSecretBox('encrypted-secret');

        $model = new UserAdminModel();
        $model->setRoles([Roles::USER]);
        $model->setPlainPassword('newPassword123');
        $model->setTotpConfirmed(false);

        $recoveryToken = $manager->update($user, $model);

        self::assertSame('recovery-token-abc123', $recoveryToken);
        self::assertTrue($user->isPasswordChangeRequired());
    }

    public function testUpdateWithPasswordChangeWithoutMailCrypt(): void
    {
        /** @var PasswordUpdater&MockObject $passwordUpdater */
        $passwordUpdater = $this->createMock(PasswordUpdater::class);
        $passwordUpdater->expects($this->once())->method('updatePassword');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $manager = new UserManager(
            $entityManager,
            $this->repository,
            $passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->settingsService,
            $this->domainGuesser,
            $this->userResetService,
            $this->deleteHandler,
            $this->aliasRepository,
            $this->voucherRepository,
            $this->openPgpKeyRepository,
            $this->userNotificationRepository,
        );

        $user = new User('user@example.org');

        $model = new UserAdminModel();
        $model->setRoles([Roles::USER]);
        $model->setPlainPassword('newPassword123');
        $model->setTotpConfirmed(false);

        $recoveryToken = $manager->update($user, $model);

        self::assertNull($recoveryToken);
        self::assertTrue($user->isPasswordChangeRequired());
    }

    public function testUpdateDeactivates2fa(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $manager = new UserManager(
            $entityManager,
            $this->repository,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->settingsService,
            $this->domainGuesser,
            $this->userResetService,
            $this->deleteHandler,
            $this->aliasRepository,
            $this->voucherRepository,
            $this->openPgpKeyRepository,
            $this->userNotificationRepository,
        );

        $user = new User('user@example.org');
        $user->setTotpSecret('totp-secret');
        $user->setTotpConfirmed(true);
        $user->setTotpBackupCodes(['code1', 'code2']);

        $model = new UserAdminModel();
        $model->setRoles([Roles::USER]);
        $model->setTotpConfirmed(false);

        $manager->update($user, $model);

        self::assertNull($user->getTotpSecret());
        self::assertFalse($user->isTotpAuthenticationEnabled());
        self::assertEmpty($user->getTotpBackupCodes());
    }

    public function testDelete(): void
    {
        $user = new User('user@example.org');

        /** @var DeleteHandler&MockObject $deleteHandler */
        $deleteHandler = $this->createMock(DeleteHandler::class);
        $deleteHandler->expects($this->once())->method('deleteUser')->with($user);

        $manager = new UserManager(
            $this->entityManager,
            $this->repository,
            $this->passwordUpdater,
            $this->mailCryptKeyHandler,
            $this->settingsService,
            $this->domainGuesser,
            $this->userResetService,
            $deleteHandler,
            $this->aliasRepository,
            $this->voucherRepository,
            $this->openPgpKeyRepository,
            $this->userNotificationRepository,
        );

        $manager->delete($user);
    }
}
