<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ApiTokenDeleteCommand;
use App\Entity\ApiToken;
use App\Service\ApiTokenManager;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ApiTokenDeleteCommandTest extends TestCase
{
    private ApiTokenDeleteCommand $command;
    private Stub&ApiTokenManager $apiTokenManager;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->apiTokenManager = $this->createStub(ApiTokenManager::class);
        $this->command = new ApiTokenDeleteCommand($this->apiTokenManager);

        $application = new Application();
        $application->addCommand($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testDeleteSuccess(): void
    {
        $plainToken = 'plain-abc';
        $apiToken = $this->createApiToken(1);

        $apiTokenManager = $this->createMock(ApiTokenManager::class);

        $apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($plainToken)
            ->willReturn($apiToken);

        $apiTokenManager
            ->expects($this->once())
            ->method('delete')
            ->with($apiToken);

        $command = new ApiTokenDeleteCommand($apiTokenManager);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--token' => $plainToken,
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('API token deleted successfully.', $output);
    }

    public function testDeleteMissingTokenOption(): void
    {
        $apiTokenManager = $this->createMock(ApiTokenManager::class);
        $apiTokenManager->expects($this->once())->method('findOne');
        $apiTokenManager->expects($this->never())->method('delete');

        $command = new ApiTokenDeleteCommand($apiTokenManager);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('[ERROR] API token not found.', $output);
    }

    public function testDeleteTokenNotFound(): void
    {
        $plainToken = 'plain-missing';

        $apiTokenManager = $this->createMock(ApiTokenManager::class);

        $apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($plainToken)
            ->willReturn(null);

        $apiTokenManager->expects($this->never())->method('delete');

        $command = new ApiTokenDeleteCommand($apiTokenManager);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--token' => $plainToken,
        ]);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('API token not found.', $output);
    }

    public function testCommandConfiguration(): void
    {
        self::assertEquals('app:api-token:delete', $this->command->getName());

        $definition = $this->command->getDefinition();
        self::assertFalse($definition->hasArgument('token'));
        self::assertTrue($definition->hasOption('token'));
    }

    private function createApiToken(int $id): ApiToken
    {
        $apiToken = $this->createStub(ApiToken::class);
        $apiToken->method('getId')->willReturn($id);

        return $apiToken;
    }
}
