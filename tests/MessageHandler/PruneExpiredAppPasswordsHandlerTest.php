<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Message\PruneExpiredAppPasswords;
use App\MessageHandler\PruneExpiredAppPasswordsHandler;
use App\Repository\AppPasswordRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PruneExpiredAppPasswordsHandlerTest extends TestCase
{
    public function testDeletesExpiredAppPasswords(): void
    {
        $repository = $this->createMock(AppPasswordRepository::class);
        $repository->expects($this->once())->method('deleteExpired')->willReturn(2);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')
            ->with('Pruned expired app passwords', ['deleted' => 2]);

        new PruneExpiredAppPasswordsHandler($repository, $logger)(new PruneExpiredAppPasswords());
    }
}
