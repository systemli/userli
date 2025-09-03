<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ApiTokenCreateCommand;
use App\Entity\ApiToken;
use App\Enum\ApiScope;
use App\Service\ApiTokenManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ApiTokenCreateCommandTest extends TestCase
{
    private ApiTokenCreateCommand $command;
    private MockObject|ApiTokenManager $apiTokenManager;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->apiTokenManager = $this->createMock(ApiTokenManager::class);
        $this->command = new ApiTokenCreateCommand($this->apiTokenManager);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccessWithSingleScope(): void
    {
        $tokenName = 'Test Token';
        $plainToken = 'generated-plain-token-123';
        $scopes = ['keycloak'];

        $apiToken = $this->createApiToken(1, $tokenName, $scopes);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('generateToken')
            ->willReturn($plainToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('create')
            ->with($plainToken, $tokenName, $scopes)
            ->willReturn($apiToken);

        $exitCode = $this->commandTester->execute([
            'name' => $tokenName,
            '--scopes' => $scopes,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('API token created successfully!', $output);
        $this->assertStringContainsString($plainToken, $output); // Token should always be displayed
        $this->assertStringContainsString('SECURITY WARNING', $output);
    }

    public function testExecuteSuccessWithMultipleScopes(): void
    {
        $tokenName = 'Multi Scope Token';
        $plainToken = 'generated-plain-token-456';
        $scopes = ['keycloak', 'dovecot', 'postfix'];

        $apiToken = $this->createApiToken(2, $tokenName, $scopes);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('generateToken')
            ->willReturn($plainToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('create')
            ->with($plainToken, $tokenName, $scopes)
            ->willReturn($apiToken);

        $exitCode = $this->commandTester->execute([
            'name' => $tokenName,
            '--scopes' => $scopes,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('API token created successfully!', $output);
        $this->assertStringContainsString($plainToken, $output); // Token should always be displayed
        $this->assertStringContainsString('SECURITY WARNING', $output);
    }

    public function testExecuteSuccessWithAllScopes(): void
    {
        $tokenName = 'Full Access Token';
        $plainToken = 'generated-plain-token-789';
        $allScopes = ApiScope::all();

        $apiToken = $this->createApiToken(3, $tokenName, $allScopes);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('generateToken')
            ->willReturn($plainToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('create')
            ->with($plainToken, $tokenName, $allScopes)
            ->willReturn($apiToken);

        $exitCode = $this->commandTester->execute([
            'name' => $tokenName,
            '--all-scopes' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('API token created successfully!', $output);
        $this->assertStringContainsString('Using all available scopes', $output);
        $this->assertStringContainsString($plainToken, $output); // Token should always be displayed
        $this->assertStringContainsString('SECURITY WARNING', $output);
    }

    public function testExecuteFailureWithNoScopes(): void
    {
        $this->apiTokenManager
            ->expects($this->never())
            ->method('generateToken');

        $this->apiTokenManager
            ->expects($this->never())
            ->method('create');

        $exitCode = $this->commandTester->execute([
            'name' => 'No Scope Token',
        ]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('You must specify at least one scope', $output);
        $this->assertStringContainsString('Available scopes:', $output);
    }

    public function testExecuteFailureWithInvalidScope(): void
    {
        $this->apiTokenManager
            ->expects($this->never())
            ->method('generateToken');

        $this->apiTokenManager
            ->expects($this->never())
            ->method('create');

        $exitCode = $this->commandTester->execute([
            'name' => 'Invalid Scope Token',
            '--scopes' => ['keycloak', 'invalid-scope', 'dovecot'],
        ]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Invalid scope(s): invalid-scope', $output);
        $this->assertStringContainsString('Available scopes:', $output);
    }

    public function testExecuteFailureWithApiTokenManagerException(): void
    {
        $tokenName = 'Error Token';
        $plainToken = 'generated-plain-token-error';
        $scopes = ['keycloak'];

        $this->apiTokenManager
            ->expects($this->once())
            ->method('generateToken')
            ->willReturn($plainToken);

        $this->apiTokenManager
            ->expects($this->once())
            ->method('create')
            ->with($plainToken, $tokenName, $scopes)
            ->willThrowException(new \RuntimeException('Database error'));

        $exitCode = $this->commandTester->execute([
            'name' => $tokenName,
            '--scopes' => $scopes,
        ]);

        $this->assertEquals(Command::FAILURE, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Failed to create API token: Database error', $output);
    }

    public function testCommandConfiguration(): void
    {
        $this->assertEquals('app:api-token:create', $this->command->getName());
        $this->assertEquals('Create a new API token with specified name and scopes', $this->command->getDescription());

        $definition = $this->command->getDefinition();

        // Test arguments
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->getArgument('name')->isRequired());

        // Test options
        $this->assertTrue($definition->hasOption('scopes'));
        $this->assertTrue($definition->hasOption('all-scopes'));
        $this->assertFalse($definition->hasOption('print-token')); // Option should be removed

        $this->assertTrue($definition->getOption('scopes')->isArray());
        $this->assertFalse($definition->getOption('all-scopes')->acceptValue());
    }

    private function createApiToken(int $id, string $name, array $scopes): ApiToken
    {
        $apiToken = $this->createMock(ApiToken::class);
        $apiToken->method('getId')->willReturn($id);
        $apiToken->method('getName')->willReturn($name);
        $apiToken->method('getScopes')->willReturn($scopes);
        $apiToken->method('getCreationTime')->willReturn(new \DateTimeImmutable('2025-09-03 10:00:00'));

        return $apiToken;
    }
}
