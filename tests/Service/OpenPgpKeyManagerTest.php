<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\PaginatedResult;
use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Repository\OpenPgpKeyRepository;
use App\Service\OpenPgpKeyManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class OpenPgpKeyManagerTest extends TestCase
{
    private string $email = 'alice@example.org';
    private string $keyData = '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';
    private string $keyId = 'AD8D52CD2281FEC2';
    private string $keyFingerprint = '7301 2547 C25D E2A0 D097  8C46 AD8D 52CD 2281 FEC2';
    private string $keyExpireTime = '@1665415900';

    private function createManager(?OpenPgpKey $existingKey = null): OpenPgpKeyManager
    {
        $repository = $this->createStub(OpenPgpKeyRepository::class);
        $repository->method('findByEmail')->willReturn($existingKey);

        $em = $this->createStub(EntityManagerInterface::class);

        return new OpenPgpKeyManager($em, $repository);
    }

    private function createExistingKey(): OpenPgpKey
    {
        $key = new OpenPgpKey();
        $key->setEmail($this->email);
        $key->setKeyId($this->keyId);
        $key->setKeyFingerprint($this->keyFingerprint);
        $key->setKeyExpireTime(new DateTimeImmutable($this->keyExpireTime));
        $key->setKeyData($this->keyData);

        return $key;
    }

    public function testFindPaginatedReturnsResult(): void
    {
        $repo = $this->createMock(OpenPgpKeyRepository::class);
        $repo->expects($this->once())
            ->method('countBySearch')
            ->with('')
            ->willReturn(2);
        $repo->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('', 20, 0)
            ->willReturn(['item1', 'item2']);

        $em = $this->createStub(EntityManagerInterface::class);
        $manager = new OpenPgpKeyManager($em, $repo);
        $result = $manager->findPaginated(1);

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertSame(['item1', 'item2'], $result->items);
        self::assertSame(1, $result->page);
        self::assertSame(1, $result->totalPages);
        self::assertSame(2, $result->total);
    }

    public function testFindPaginatedPassesSearch(): void
    {
        $repo = $this->createMock(OpenPgpKeyRepository::class);
        $repo->expects($this->once())
            ->method('countBySearch')
            ->with('user@example.org')
            ->willReturn(1);
        $repo->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('user@example.org', 20, 0)
            ->willReturn(['item1']);

        $em = $this->createStub(EntityManagerInterface::class);
        $manager = new OpenPgpKeyManager($em, $repo);
        $result = $manager->findPaginated(1, 'user@example.org');

        self::assertSame(['item1'], $result->items);
        self::assertSame(1, $result->total);
    }

    public function testFindPaginatedCalculatesOffset(): void
    {
        $repo = $this->createMock(OpenPgpKeyRepository::class);
        $repo->expects($this->once())
            ->method('countBySearch')
            ->with('')
            ->willReturn(50);
        $repo->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('', 20, 20)
            ->willReturn([]);

        $em = $this->createStub(EntityManagerInterface::class);
        $manager = new OpenPgpKeyManager($em, $repo);
        $result = $manager->findPaginated(2);

        self::assertSame(2, $result->page);
        self::assertSame(3, $result->totalPages);
        self::assertSame(50, $result->total);
    }

    public function testFindPaginatedClampsPageToMinimumOne(): void
    {
        $repo = $this->createMock(OpenPgpKeyRepository::class);
        $repo->expects($this->once())
            ->method('countBySearch')
            ->willReturn(0);
        $repo->expects($this->once())
            ->method('findPaginatedBySearch')
            ->with('', 20, 0)
            ->willReturn([]);

        $em = $this->createStub(EntityManagerInterface::class);
        $manager = new OpenPgpKeyManager($em, $repo);
        $result = $manager->findPaginated(-5);

        self::assertSame(1, $result->page);
        self::assertSame(1, $result->totalPages);
    }

    public function testImportKey(): void
    {
        $existingKey = $this->createExistingKey();
        $manager = $this->createManager($existingKey);

        $expected = new OpenPgpKey();
        $expected->setEmail($this->email);
        $expected->setKeyId($this->keyId);
        $expected->setKeyFingerprint($this->keyFingerprint);
        $expected->setKeyExpireTime(new DateTimeImmutable($this->keyExpireTime));
        $expected->setKeyData($this->keyData);
        $expected->setWkdHash(OpenPgpKeyManager::wkdHash('alice'));

        $wkdKey = $manager->importKey(base64_decode($this->keyData), $this->email);

        self::assertEquals($expected, $wkdKey);
    }

    public function testImportKeyWithUser(): void
    {
        $existingKey = $this->createExistingKey();
        $manager = $this->createManager($existingKey);

        $domain = new Domain();
        $domain->setName(explode('@', $this->email)[1]);
        $user = new User($this->email);
        $user->setDomain($domain);

        $expected = new OpenPgpKey();
        $expected->setEmail($this->email);
        $expected->setKeyId($this->keyId);
        $expected->setKeyFingerprint($this->keyFingerprint);
        $expected->setKeyExpireTime(new DateTimeImmutable($this->keyExpireTime));
        $expected->setKeyData($this->keyData);
        $expected->setUploader($user);
        $expected->setWkdHash(OpenPgpKeyManager::wkdHash('alice'));

        $wkdKey = $manager->importKey(base64_decode($this->keyData), $this->email, $user);

        self::assertEquals($expected, $wkdKey);
    }

    public function testDeleteByEmail(): void
    {
        $openPgpKey = new OpenPgpKey();
        $openPgpKey->setEmail($this->email);

        $repository = $this->createStub(OpenPgpKeyRepository::class);
        $repository->method('findByEmail')->willReturn($openPgpKey);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($openPgpKey);
        $em->expects($this->once())->method('flush');

        $manager = new OpenPgpKeyManager($em, $repository);
        $manager->deleteKey($this->email);
    }

    public function testDeleteByEmailNotFound(): void
    {
        $repository = $this->createStub(OpenPgpKeyRepository::class);
        $repository->method('findByEmail')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');

        $manager = new OpenPgpKeyManager($em, $repository);
        $manager->deleteKey($this->email);
    }

    public function testGetKey(): void
    {
        $existingKey = $this->createExistingKey();
        $manager = $this->createManager($existingKey);
        $key = $manager->getKey($this->email);

        self::assertNotNull($key);
        self::assertEquals($this->email, $key->getEmail());
    }

    public function testWkdHash(): void
    {
        self::assertEquals('kei1q4tipxxu1yj79k9kfukdhfy631xe', OpenPgpKeyManager::wkdHash('alice'));
    }
}
