<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ReservedName;
use App\Repository\ReservedNameRepository;
use App\Service\ReservedNameManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ReservedNameManagerTest extends TestCase
{
    private ReservedNameRepository&Stub $repository;
    private EntityManagerInterface&Stub $entityManager;
    private ReservedNameManager $manager;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(ReservedNameRepository::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);

        $this->manager = new ReservedNameManager(
            $this->entityManager,
            $this->repository,
        );
    }

    public function testCreate(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ReservedName::class));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $manager = new ReservedNameManager($entityManager, $this->repository);
        $result = $manager->create('testname');

        self::assertInstanceOf(ReservedName::class, $result);
        self::assertEquals('testname', $result->getName());
    }

    public function testDelete(): void
    {
        $reservedName = new ReservedName();
        $reservedName->setName('deleteme');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($reservedName);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $manager = new ReservedNameManager($entityManager, $this->repository);
        $manager->delete($reservedName);
    }

    public function testFindPaginatedDefaults(): void
    {
        $items = [new ReservedName(), new ReservedName()];

        $repository = $this->createMock(ReservedNameRepository::class);
        $repository
            ->expects($this->once())
            ->method('countBySearch')
            ->with('')
            ->willReturn(2);
        $repository
            ->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('', 20, 0)
            ->willReturn($items);

        $manager = new ReservedNameManager($this->entityManager, $repository);
        $result = $manager->findPaginated();

        self::assertSame($items, $result['items']);
        self::assertEquals(1, $result['page']);
        self::assertEquals(1, $result['totalPages']);
        self::assertEquals(2, $result['total']);
    }

    public function testFindPaginatedWithSearch(): void
    {
        $repository = $this->createMock(ReservedNameRepository::class);
        $repository
            ->expects($this->once())
            ->method('countBySearch')
            ->with('admin')
            ->willReturn(1);
        $repository
            ->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('admin', 20, 0)
            ->willReturn([new ReservedName()]);

        $manager = new ReservedNameManager($this->entityManager, $repository);
        $result = $manager->findPaginated(1, 'admin');

        self::assertCount(1, $result['items']);
        self::assertEquals(1, $result['total']);
    }

    public function testFindPaginatedWithMultiplePages(): void
    {
        $repository = $this->createMock(ReservedNameRepository::class);
        $repository
            ->expects($this->once())
            ->method('countBySearch')
            ->with('')
            ->willReturn(45);
        $repository
            ->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('', 20, 20)
            ->willReturn([new ReservedName()]);

        $manager = new ReservedNameManager($this->entityManager, $repository);
        $result = $manager->findPaginated(2);

        self::assertEquals(2, $result['page']);
        self::assertEquals(3, $result['totalPages']);
        self::assertEquals(45, $result['total']);
    }

    public function testFindPaginatedNegativePageClampedToOne(): void
    {
        $repository = $this->createMock(ReservedNameRepository::class);
        $repository->method('countBySearch')->willReturn(5);
        $repository
            ->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('', 20, 0);

        $manager = new ReservedNameManager($this->entityManager, $repository);
        $result = $manager->findPaginated(-1);

        self::assertEquals(1, $result['page']);
    }

    public function testFindPaginatedZeroTotalReturnsOneTotalPage(): void
    {
        $repository = $this->createMock(ReservedNameRepository::class);
        $repository->method('countBySearch')->willReturn(0);
        $repository->method('findPaginatedBySearch')->willReturn([]);

        $manager = new ReservedNameManager($this->entityManager, $repository);
        $result = $manager->findPaginated();

        self::assertEquals(1, $result['totalPages']);
        self::assertEquals(0, $result['total']);
        self::assertEmpty($result['items']);
    }

    public function testImportFromFile(): void
    {
        $content = "admin\npostmaster\nwebmaster\n";
        $tmpFile = $this->createTempFile($content);

        $this->repository->method('findByName')->willReturn(null);

        $file = new UploadedFile($tmpFile, 'reserved_names.txt', 'text/plain', null, true);
        $result = $this->manager->importFromFile($file);

        self::assertEquals(3, $result['imported']);
        self::assertEquals(0, $result['skipped']);
    }

    public function testImportFromFileSkipsExistingNames(): void
    {
        $content = "admin\npostmaster\n";
        $tmpFile = $this->createTempFile($content);

        $this->repository->method('findByName')->willReturnMap([
            ['admin', new ReservedName()],
            ['postmaster', null],
        ]);

        $file = new UploadedFile($tmpFile, 'reserved_names.txt', 'text/plain', null, true);
        $result = $this->manager->importFromFile($file);

        self::assertEquals(1, $result['imported']);
        self::assertEquals(1, $result['skipped']);
    }

    public function testImportFromFileSkipsComments(): void
    {
        $content = "# This is a comment\nadmin\n# Another comment\n";
        $tmpFile = $this->createTempFile($content);

        $this->repository->method('findByName')->willReturn(null);

        $file = new UploadedFile($tmpFile, 'reserved_names.txt', 'text/plain', null, true);
        $result = $this->manager->importFromFile($file);

        self::assertEquals(1, $result['imported']);
        self::assertEquals(0, $result['skipped']);
    }

    public function testImportFromFileSkipsEmptyLines(): void
    {
        $content = "\n\nadmin\n\n\n";
        $tmpFile = $this->createTempFile($content);

        $this->repository->method('findByName')->willReturn(null);

        $file = new UploadedFile($tmpFile, 'reserved_names.txt', 'text/plain', null, true);
        $result = $this->manager->importFromFile($file);

        self::assertEquals(1, $result['imported']);
        self::assertEquals(0, $result['skipped']);
    }

    public function testImportFromFileLowercasesNames(): void
    {
        $content = "Admin\nPOSTMASTER\nWebMaster\n";
        $tmpFile = $this->createTempFile($content);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $persistedNames = [];
        $entityManager->method('persist')->willReturnCallback(
            static function (ReservedName $reservedName) use (&$persistedNames): void {
                $persistedNames[] = $reservedName->getName();
            }
        );

        $this->repository->method('findByName')->willReturn(null);

        $manager = new ReservedNameManager($entityManager, $this->repository);
        $file = new UploadedFile($tmpFile, 'reserved_names.txt', 'text/plain', null, true);
        $manager->importFromFile($file);

        self::assertEquals(['admin', 'postmaster', 'webmaster'], $persistedNames);
    }

    public function testImportFromFileEmptyFile(): void
    {
        $tmpFile = $this->createTempFile('');

        $file = new UploadedFile($tmpFile, 'reserved_names.txt', 'text/plain', null, true);
        $result = $this->manager->importFromFile($file);

        self::assertEquals(0, $result['imported']);
        self::assertEquals(0, $result['skipped']);
    }

    public function testExportAsText(): void
    {
        $name1 = new ReservedName();
        $name1->setName('admin');
        $name2 = new ReservedName();
        $name2->setName('postmaster');
        $name3 = new ReservedName();
        $name3->setName('webmaster');

        $this->repository->method('findBy')
            ->with([], ['name' => 'ASC'])
            ->willReturn([$name1, $name2, $name3]);

        $result = $this->manager->exportAsText();

        self::assertEquals("admin\npostmaster\nwebmaster\n", $result);
    }

    public function testExportAsTextEmpty(): void
    {
        $this->repository->method('findBy')->willReturn([]);

        $result = $this->manager->exportAsText();

        self::assertEquals("\n", $result);
    }

    private function createTempFile(string $content): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'reserved_names_test_');
        file_put_contents($tmpFile, $content);

        return $tmpFile;
    }
}
