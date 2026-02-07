<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ApiTokenManager
{
    public function __construct(
        private ApiTokenRepository $apiTokenRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function create(string $plainToken, string $name, array $scopes): ApiToken
    {
        $hashedToken = $this->hashToken($plainToken);
        $token = new ApiToken(token: $hashedToken, name: $name, scopes: $scopes);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function findOne(string $plainToken): ?ApiToken
    {
        $hashedToken = $this->hashToken($plainToken);

        return $this->apiTokenRepository->findOneBy(['token' => $hashedToken]);
    }

    /**
     * Updates the last used time of the API token.
     *
     * The update is throttled to avoid unnecessary database writes on every request.
     * The last used time is only updated if it has not been set yet or if the last
     * update was more than 5 minutes ago.
     */
    public function updateLastUsedTime(ApiToken $token): void
    {
        $lastUsedTime = $token->getLastUsedTime();

        if ($lastUsedTime !== null && $lastUsedTime >= new DateTimeImmutable('-5 minutes')) {
            return;
        }

        $this->apiTokenRepository->updateLastUsedTime($token);
    }

    public function findAll(): array
    {
        return $this->apiTokenRepository->findAll();
    }

    public function delete(ApiToken $apiToken): void
    {
        $this->entityManager->remove($apiToken);
        $this->entityManager->flush();
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
