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
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiTokenCreateCommandTest extends TestCase
{
    private ApiTokenCreateCommand $command;
    private MockObject|ApiTokenManager $apiTokenManager;
    private MockObject|ValidatorInterface $validator;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->apiTokenManager = $this->createMock(ApiTokenManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        // By default, validation passes (no violations)
        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList([]));

        $this->command = new ApiTokenCreateCommand($this->apiTokenManager, $this->validator);

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
            '--name' => $tokenName,
            '--scopes' => $scopes,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Store this token securely - it cannot be retrieved again.', $output);
        $this->assertStringContainsString($plainToken, $output); // Token should always be displayed
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
            '--name' => $tokenName,
            '--scopes' => $scopes,
        ]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Store this token securely - it cannot be retrieved again.', $output);
        $this->assertStringContainsString($plainToken, $output); // Token should always be displayed
    }

    public function testExecuteFailureWithInvalidName(): void
    {
        // Fresh validator mock tailored for this test
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new \Symfony\Component\Validator\ConstraintViolation(
                'This value is too short.',
                null,
                [],
                '',
                'name',
                'abc'
            )
        ]));

        $command = new ApiTokenCreateCommand($this->apiTokenManager, $validator);
        $tester = new CommandTester($command);

        $this->apiTokenManager->expects($this->never())->method('generateToken');
        $this->apiTokenManager->expects($this->never())->method('create');

        $exitCode = $tester->execute([
            '--name' => 'abc',
            '--scopes' => ['keycloak'],
        ]);

        $this->assertEquals(Command::FAILURE, $exitCode);
    $output = $tester->getDisplay();
        $this->assertStringContainsString('Validation failed:', $output);
    }

    public function testExecuteFailureWithInvalidScope(): void
    {
        // Fresh validator mock tailored for this test
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new \Symfony\Component\Validator\ConstraintViolation(
                'One or more of the given values is invalid.',
                null,
                [],
                '',
                'scopes',
                ['keycloak', 'invalid-scope', 'dovecot']
            )
        ]));

        $command = new ApiTokenCreateCommand($this->apiTokenManager, $validator);
        $tester = new CommandTester($command);

        $this->apiTokenManager->expects($this->never())->method('generateToken');
        $this->apiTokenManager->expects($this->never())->method('create');

        $exitCode = $tester->execute([
            '--name' => 'Invalid Scope Token',
            '--scopes' => ['keycloak', 'invalid-scope', 'dovecot'],
        ]);

        $this->assertEquals(Command::FAILURE, $exitCode);
    $output = $tester->getDisplay();
        $this->assertStringContainsString('Validation failed:', $output);
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
            '--name' => $tokenName,
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

        // Arguments: none required anymore for name
        $this->assertFalse($definition->hasArgument('name'));

        // Options
        $this->assertTrue($definition->hasOption('name'));
        $this->assertTrue($definition->hasOption('scopes'));
        $this->assertFalse($definition->hasOption('all-scopes'));
        $this->assertFalse($definition->hasOption('print-token')); // Option should be removed

        $this->assertTrue($definition->getOption('scopes')->isArray());
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
