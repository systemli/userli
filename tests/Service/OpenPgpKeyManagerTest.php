<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\GpgKeyResult;
use App\Dto\PaginatedResult;
use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Repository\OpenPgpKeyRepository;
use App\Service\DomainGuesser;
use App\Service\GpgKeyParser;
use App\Service\OpenPgpKeyManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class OpenPgpKeyManagerTest extends TestCase
{
    private string $email = 'alice@example.org';
    private string $keyData = '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';
    private string $keyId = 'AD8D52CD2281FEC2';
    private string $keyFingerprint = '7301 2547 C25D E2A0 D097  8C46 AD8D 52CD 2281 FEC2';
    private string $keyExpireTime = '@1665415900';

    private function createGpgKeyResult(): GpgKeyResult
    {
        return new GpgKeyResult(
            email: $this->email,
            keyId: $this->keyId,
            fingerprint: $this->keyFingerprint,
            expireTime: new DateTimeImmutable($this->keyExpireTime),
            keyData: $this->keyData,
        );
    }

    private function createManager(?OpenPgpKey $existingKey = null, ?Domain $domain = null): OpenPgpKeyManager
    {
        $repository = $this->createStub(OpenPgpKeyRepository::class);
        $repository->method('findByEmail')->willReturn($existingKey);

        $em = $this->createStub(EntityManagerInterface::class);

        if (null === $domain) {
            $domain = new Domain();
            $domain->setName('example.org');
        }

        $domainGuesser = $this->createStub(DomainGuesser::class);
        $domainGuesser->method('guess')->willReturn($domain);

        $gpgKeyParser = $this->createStub(GpgKeyParser::class);
        $gpgKeyParser->method('parse')->willReturn($this->createGpgKeyResult());

        return new OpenPgpKeyManager($em, $repository, $domainGuesser, $gpgKeyParser);
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
        $domainGuesser = $this->createStub(DomainGuesser::class);
        $gpgKeyParser = $this->createStub(GpgKeyParser::class);
        $manager = new OpenPgpKeyManager($em, $repo, $domainGuesser, $gpgKeyParser);
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
        $domainGuesser = $this->createStub(DomainGuesser::class);
        $gpgKeyParser = $this->createStub(GpgKeyParser::class);
        $manager = new OpenPgpKeyManager($em, $repo, $domainGuesser, $gpgKeyParser);
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
        $domainGuesser = $this->createStub(DomainGuesser::class);
        $gpgKeyParser = $this->createStub(GpgKeyParser::class);
        $manager = new OpenPgpKeyManager($em, $repo, $domainGuesser, $gpgKeyParser);
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
        $domainGuesser = $this->createStub(DomainGuesser::class);
        $gpgKeyParser = $this->createStub(GpgKeyParser::class);
        $manager = new OpenPgpKeyManager($em, $repo, $domainGuesser, $gpgKeyParser);
        $result = $manager->findPaginated(-5);

        self::assertSame(1, $result->page);
        self::assertSame(1, $result->totalPages);
    }

    public function testImportKey(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $existingKey = new OpenPgpKey();
        $existingKey->setEmail($this->email);
        $manager = $this->createManager($existingKey, $domain);

        $expected = new OpenPgpKey();
        $expected->setEmail($this->email);
        $expected->setKeyId($this->keyId);
        $expected->setKeyFingerprint($this->keyFingerprint);
        $expected->setKeyExpireTime(new DateTimeImmutable($this->keyExpireTime));
        $expected->setKeyData($this->keyData);
        $expected->setWkdHash(OpenPgpKeyManager::wkdHash('alice'));
        $expected->setDomain($domain);

        $wkdKey = $manager->importKey(base64_decode($this->keyData), $this->email);

        self::assertEquals($expected, $wkdKey);
    }

    public function testImportKeyWithUser(): void
    {
        $domain = new Domain();
        $domain->setName(explode('@', $this->email)[1]);

        $existingKey = new OpenPgpKey();
        $existingKey->setEmail($this->email);
        $manager = $this->createManager($existingKey, $domain);

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
        $expected->setDomain($domain);

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

        $domainGuesser = $this->createStub(DomainGuesser::class);
        $gpgKeyParser = $this->createStub(GpgKeyParser::class);
        $manager = new OpenPgpKeyManager($em, $repository, $domainGuesser, $gpgKeyParser);
        $manager->deleteKey($this->email);
    }

    public function testDeleteByEmailNotFound(): void
    {
        $repository = $this->createStub(OpenPgpKeyRepository::class);
        $repository->method('findByEmail')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');

        $domainGuesser = $this->createStub(DomainGuesser::class);
        $gpgKeyParser = $this->createStub(GpgKeyParser::class);
        $manager = new OpenPgpKeyManager($em, $repository, $domainGuesser, $gpgKeyParser);
        $manager->deleteKey($this->email);
    }

    public function testGetKey(): void
    {
        $existingKey = new OpenPgpKey();
        $existingKey->setEmail($this->email);
        $manager = $this->createManager($existingKey);
        $key = $manager->getKey($this->email);

        self::assertNotNull($key);
        self::assertEquals($this->email, $key->getEmail());
    }

    public function testWkdHash(): void
    {
        self::assertEquals('kei1q4tipxxu1yj79k9kfukdhfy631xe', OpenPgpKeyManager::wkdHash('alice'));
    }

    public function testImportKeyThrowsExceptionForUnknownDomain(): void
    {
        $repository = $this->createStub(OpenPgpKeyRepository::class);
        $repository->method('findByEmail')->willReturn(null);

        $em = $this->createStub(EntityManagerInterface::class);

        $domainGuesser = $this->createStub(DomainGuesser::class);
        $domainGuesser->method('guess')->willReturn(null);

        $gpgKeyParser = $this->createStub(GpgKeyParser::class);
        $gpgKeyParser->method('parse')->willReturn(new GpgKeyResult(
            email: $this->email,
            keyId: $this->keyId,
            fingerprint: $this->keyFingerprint,
            expireTime: new DateTimeImmutable($this->keyExpireTime),
            keyData: $this->keyData,
        ));

        $manager = new OpenPgpKeyManager($em, $repository, $domainGuesser, $gpgKeyParser);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No matching domain found for email "alice@example.org"');

        $manager->importKey(base64_decode($this->keyData), $this->email);
    }
}
