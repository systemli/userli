<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use App\Service\ApiTokenManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class ApiTokenManagerTest extends TestCase
{
    private ApiTokenRepository&Stub $apiTokenRepository;
    private EntityManagerInterface&Stub $entityManager;
    private ApiTokenManager $apiTokenManager;

    protected function setUp(): void
    {
        $this->apiTokenRepository = $this->createStub(ApiTokenRepository::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);

        $this->apiTokenManager = new ApiTokenManager(
            $this->apiTokenRepository,
            $this->entityManager
        );
    }

    public function testCreate(): void
    {
        $plainToken = 'test-token-123';
        $name = 'Test Token';
        $scopes = ['dovecot', 'keycloak'];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ApiToken::class));

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $apiTokenManager = new ApiTokenManager(
            $this->apiTokenRepository,
            $entityManager
        );

        $result = $apiTokenManager->create($plainToken, $name, $scopes);

        self::assertInstanceOf(ApiToken::class, $result);
        self::assertEquals($name, $result->getName());
        self::assertEquals($scopes, $result->getScopes());
        self::assertEquals($apiTokenManager->hashToken($plainToken), $result->getToken());
    }

    public function testFindOne(): void
    {
        $plainToken = 'test-token-123';
        $hashedToken = $this->apiTokenManager->hashToken($plainToken);
        $expectedApiToken = new ApiToken($hashedToken, 'Test Token', ['dovecot']);

        $apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $apiTokenRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $hashedToken])
            ->willReturn($expectedApiToken);

        $apiTokenManager = new ApiTokenManager($apiTokenRepository, $this->entityManager);
        $result = $apiTokenManager->findOne($plainToken);

        self::assertSame($expectedApiToken, $result);
    }

    public function testFindOneReturnsNullWhenTokenNotFound(): void
    {
        $plainToken = 'non-existent-token';
        $hashedToken = $this->apiTokenManager->hashToken($plainToken);

        $apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $apiTokenRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $hashedToken])
            ->willReturn(null);

        $apiTokenManager = new ApiTokenManager($apiTokenRepository, $this->entityManager);
        $result = $apiTokenManager->findOne($plainToken);

        self::assertNull($result);
    }

    public function testUpdateLastUsedTimeWhenNeverUsed(): void
    {
        $apiToken = new ApiToken('hashed-token', 'Test Token', ['dovecot']);

        // lastUsedTime is null by default, so it should be updated
        $apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $apiTokenRepository
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        $apiTokenManager = new ApiTokenManager($apiTokenRepository, $this->entityManager);
        $apiTokenManager->updateLastUsedTime($apiToken);
    }

    public function testUpdateLastUsedTimeWhenLastUsedMoreThanFiveMinutesAgo(): void
    {
        $apiToken = new ApiToken('hashed-token', 'Test Token', ['dovecot']);
        $apiToken->setLastUsedTime(new DateTimeImmutable('-10 minutes'));

        $apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $apiTokenRepository
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        $apiTokenManager = new ApiTokenManager($apiTokenRepository, $this->entityManager);
        $apiTokenManager->updateLastUsedTime($apiToken);
    }

    public function testUpdateLastUsedTimeSkippedWhenRecentlyUsed(): void
    {
        $apiToken = new ApiToken('hashed-token', 'Test Token', ['dovecot']);
        $apiToken->setLastUsedTime(new DateTimeImmutable('-2 minutes'));

        // Should NOT call repository since last used time is recent
        $apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $apiTokenRepository
            ->expects($this->never())
            ->method('updateLastUsedTime');

        $apiTokenManager = new ApiTokenManager($apiTokenRepository, $this->entityManager);
        $apiTokenManager->updateLastUsedTime($apiToken);
    }

    public function testUpdateLastUsedTimeJustOverFiveMinutesAgo(): void
    {
        $apiToken = new ApiToken('hashed-token', 'Test Token', ['dovecot']);
        $apiToken->setLastUsedTime(new DateTimeImmutable('-5 minutes -1 second'));

        $apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $apiTokenRepository
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        $apiTokenManager = new ApiTokenManager($apiTokenRepository, $this->entityManager);
        $apiTokenManager->updateLastUsedTime($apiToken);
    }

    public function testFindAll(): void
    {
        $expectedTokens = [
            new ApiToken('token1', 'Token 1', ['dovecot']),
            new ApiToken('token2', 'Token 2', ['keycloak']),
        ];

        $apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $apiTokenRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedTokens);

        $apiTokenManager = new ApiTokenManager($apiTokenRepository, $this->entityManager);
        $result = $apiTokenManager->findAll();

        self::assertSame($expectedTokens, $result);
    }

    public function testDelete(): void
    {
        $apiToken = new ApiToken('hashed-token', 'Test Token', ['dovecot']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($apiToken);

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $apiTokenManager = new ApiTokenManager($this->apiTokenRepository, $entityManager);
        $apiTokenManager->delete($apiToken);
    }

    public function testGenerateToken(): void
    {
        $token1 = $this->apiTokenManager->generateToken();
        $token2 = $this->apiTokenManager->generateToken();

        // Test that tokens are generated
        self::assertIsString($token1);
        self::assertIsString($token2);

        // Test that tokens are 64 characters long (32 bytes * 2 for hex)
        self::assertEquals(64, strlen($token1));
        self::assertEquals(64, strlen($token2));

        // Test that tokens are different (randomness)
        self::assertNotEquals($token1, $token2);

        // Test that tokens are valid hex strings
        self::assertMatchesRegularExpression('/^[a-f0-9]+$/', $token1);
        self::assertMatchesRegularExpression('/^[a-f0-9]+$/', $token2);
    }

    public function testHashToken(): void
    {
        $plainToken = 'test-token-123';
        $expectedHash = hash('sha256', $plainToken);

        $result = $this->apiTokenManager->hashToken($plainToken);

        self::assertEquals($expectedHash, $result);
        self::assertEquals(64, strlen($result)); // SHA256 produces 64 character hex string
    }

    public function testHashTokenConsistency(): void
    {
        $plainToken = 'test-token-123';

        $hash1 = $this->apiTokenManager->hashToken($plainToken);
        $hash2 = $this->apiTokenManager->hashToken($plainToken);

        self::assertEquals($hash1, $hash2);
    }

    public function testHashTokenDifferentInputsProduceDifferentHashes(): void
    {
        $token1 = 'token-1';
        $token2 = 'token-2';

        $hash1 = $this->apiTokenManager->hashToken($token1);
        $hash2 = $this->apiTokenManager->hashToken($token2);

        self::assertNotEquals($hash1, $hash2);
    }
}
