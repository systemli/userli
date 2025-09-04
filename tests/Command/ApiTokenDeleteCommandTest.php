<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ApiTokenDeleteCommand;
use App\Entity\ApiToken;
use App\Service\ApiTokenManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ApiTokenDeleteCommandTest extends TestCase
{
    private ApiTokenDeleteCommand $command;
    private MockObject|ApiTokenManager $apiTokenManager;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->apiTokenManager = $this->createMock(ApiTokenManager::class);
        $this->command = new ApiTokenDeleteCommand($this->apiTokenManager);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testDeleteSuccess(): void
    {
        $plainToken = 'plain-abc';
        $apiToken = $this->createApiToken(1);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($plainToken)
            ->willReturn($apiToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('delete')
            ->with($apiToken);

        $exitCode = $this->commandTester->execute([
            '--token' => $plainToken,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('API token deleted successfully.', $output);
    }

    public function testDeleteMissingTokenOption(): void
    {
        $this->apiTokenManager->expects($this->once())->method('findOne');
        $this->apiTokenManager->expects($this->never())->method('delete');

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('[ERROR] API token not found.', $output);
    }

    public function testDeleteTokenNotFound(): void
    {
        $plainToken = 'plain-missing';

        $this->apiTokenManager
            ->expects($this->once())
            ->method('findOne')
            ->with($plainToken)
            ->willReturn(null);

        $this->apiTokenManager->expects($this->never())->method('delete');

        $exitCode = $this->commandTester->execute([
            '--token' => $plainToken,
        ]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('API token not found.', $output);
    }

    public function testCommandConfiguration(): void
    {
        $this->assertEquals('app:api-token:delete', $this->command->getName());

        $definition = $this->command->getDefinition();
        $this->assertFalse($definition->hasArgument('token'));
        $this->assertTrue($definition->hasOption('token'));
    }

    private function createApiToken(int $id): ApiToken
    {
        $apiToken = $this->createMock(ApiToken::class);
        $apiToken->method('getId')->willReturn($id);

        return $apiToken;
    }
}
