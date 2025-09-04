<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use App\Service\ApiTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApiTokenManagerTest extends TestCase
{
    private ApiTokenRepository|MockObject $apiTokenRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private ApiTokenManager $apiTokenManager;

    protected function setUp(): void
    {
        $this->apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

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

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ApiToken::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->apiTokenManager->create($plainToken, $name, $scopes);

        $this->assertInstanceOf(ApiToken::class, $result);
        $this->assertEquals($name, $result->getName());
        $this->assertEquals($scopes, $result->getScopes());
        $this->assertEquals($this->apiTokenManager->hashToken($plainToken), $result->getToken());
    }

    public function testFindOne(): void
    {
        $plainToken = 'test-token-123';
        $hashedToken = $this->apiTokenManager->hashToken($plainToken);
        $expectedApiToken = new ApiToken($hashedToken, 'Test Token', ['dovecot']);

        $this->apiTokenRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $hashedToken])
            ->willReturn($expectedApiToken);

        $result = $this->apiTokenManager->findOne($plainToken);

        $this->assertSame($expectedApiToken, $result);
    }

    public function testFindOneReturnsNullWhenTokenNotFound(): void
    {
        $plainToken = 'non-existent-token';
        $hashedToken = $this->apiTokenManager->hashToken($plainToken);

        $this->apiTokenRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $hashedToken])
            ->willReturn(null);

        $result = $this->apiTokenManager->findOne($plainToken);

        $this->assertNull($result);
    }

    public function testUpdateLastUsedTime(): void
    {
        $apiToken = new ApiToken('hashed-token', 'Test Token', ['dovecot']);

        $this->apiTokenRepository
            ->expects($this->once())
            ->method('updateLastUsedTime')
            ->with($apiToken);

        $this->apiTokenManager->updateLastUsedTime($apiToken);
    }

    public function testFindAll(): void
    {
        $expectedTokens = [
            new ApiToken('token1', 'Token 1', ['dovecot']),
            new ApiToken('token2', 'Token 2', ['keycloak']),
        ];

        $this->apiTokenRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedTokens);

        $result = $this->apiTokenManager->findAll();

        $this->assertSame($expectedTokens, $result);
    }

    public function testDelete(): void
    {
        $apiToken = new ApiToken('hashed-token', 'Test Token', ['dovecot']);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($apiToken);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->apiTokenManager->delete($apiToken);
    }

    public function testGenerateToken(): void
    {
        $token1 = $this->apiTokenManager->generateToken();
        $token2 = $this->apiTokenManager->generateToken();

        // Test that tokens are generated
        $this->assertIsString($token1);
        $this->assertIsString($token2);

        // Test that tokens are 64 characters long (32 bytes * 2 for hex)
        $this->assertEquals(64, strlen($token1));
        $this->assertEquals(64, strlen($token2));

        // Test that tokens are different (randomness)
        $this->assertNotEquals($token1, $token2);

        // Test that tokens are valid hex strings
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token1);
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token2);
    }

    public function testHashToken(): void
    {
        $plainToken = 'test-token-123';
        $expectedHash = hash('sha256', $plainToken);

        $result = $this->apiTokenManager->hashToken($plainToken);

        $this->assertEquals($expectedHash, $result);
        $this->assertEquals(64, strlen($result)); // SHA256 produces 64 character hex string
    }

    public function testHashTokenConsistency(): void
    {
        $plainToken = 'test-token-123';

        $hash1 = $this->apiTokenManager->hashToken($plainToken);
        $hash2 = $this->apiTokenManager->hashToken($plainToken);

        $this->assertEquals($hash1, $hash2);
    }

    public function testHashTokenDifferentInputsProduceDifferentHashes(): void
    {
        $token1 = 'token-1';
        $token2 = 'token-2';

        $hash1 = $this->apiTokenManager->hashToken($token1);
        $hash2 = $this->apiTokenManager->hashToken($token2);

        $this->assertNotEquals($hash1, $hash2);
    }
}
