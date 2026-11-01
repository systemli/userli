<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Message\PruneExpiredWebauthnChallenges;
use App\MessageHandler\PruneExpiredWebauthnChallengesHandler;
use App\Repository\WebauthnChallengeRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PruneExpiredWebauthnChallengesHandlerTest extends TestCase
{
    public function testDeletesExpiredChallenges(): void
    {
        $repository = $this->createMock(WebauthnChallengeRepository::class);
        $repository->expects($this->once())->method('deleteExpired')->willReturn(3);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')
            ->with('Pruned expired WebAuthn challenges', ['deleted' => 3]);

        new PruneExpiredWebauthnChallengesHandler($repository, $logger)(new PruneExpiredWebauthnChallenges());
    }
}
