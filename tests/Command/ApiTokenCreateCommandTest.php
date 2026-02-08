<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ApiTokenCreateCommand;
use App\Entity\ApiToken;
use App\Service\ApiTokenManager;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiTokenCreateCommandTest extends TestCase
{
    private ApiTokenCreateCommand $command;
    private Stub&ApiTokenManager $apiTokenManager;
    private Stub&ValidatorInterface $validator;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->apiTokenManager = $this->createStub(ApiTokenManager::class);
        $this->validator = $this->createStub(ValidatorInterface::class);
        // By default, validation passes (no violations)
        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList([]));

        $this->command = new ApiTokenCreateCommand($this->apiTokenManager, $this->validator);

        $application = new Application();
        $application->addCommand($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteSuccessWithSingleScope(): void
    {
        $tokenName = 'Test Token';
        $plainToken = 'generated-plain-token-123';
        $scopes = ['keycloak'];

        $apiToken = $this->createApiToken(1, $tokenName, $scopes);

        $apiTokenManager = $this->createMock(ApiTokenManager::class);

        $apiTokenManager
            ->expects($this->once())
            ->method('generateToken')
            ->willReturn($plainToken);

        $apiTokenManager
            ->expects($this->once())
            ->method('create')
            ->with($plainToken, $tokenName, $scopes)
            ->willReturn($apiToken);

        $command = new ApiTokenCreateCommand($apiTokenManager, $this->validator);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--name' => $tokenName,
            '--scopes' => $scopes,
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Store this token securely - it cannot be retrieved again.', $output);
        self::assertStringContainsString($plainToken, $output); // Token should always be displayed
    }

    public function testExecuteSuccessWithMultipleScopes(): void
    {
        $tokenName = 'Multi Scope Token';
        $plainToken = 'generated-plain-token-456';
        $scopes = ['keycloak', 'dovecot', 'postfix'];

        $apiToken = $this->createApiToken(2, $tokenName, $scopes);

        $apiTokenManager = $this->createMock(ApiTokenManager::class);

        $apiTokenManager
            ->expects($this->once())
            ->method('generateToken')
            ->willReturn($plainToken);

        $apiTokenManager
            ->expects($this->once())
            ->method('create')
            ->with($plainToken, $tokenName, $scopes)
            ->willReturn($apiToken);

        $command = new ApiTokenCreateCommand($apiTokenManager, $this->validator);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--name' => $tokenName,
            '--scopes' => $scopes,
        ]);

        self::assertEquals(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Store this token securely - it cannot be retrieved again.', $output);
        self::assertStringContainsString($plainToken, $output); // Token should always be displayed
    }

    public function testExecuteFailureWithInvalidName(): void
    {
        // Fresh validator mock tailored for this test
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new \Symfony\Component\Validator\ConstraintViolation(
                'This value is too short.',
                null,
                [],
                '',
                'name',
                'abc'
            ),
        ]));

        $apiTokenManager = $this->createMock(ApiTokenManager::class);
        $command = new ApiTokenCreateCommand($apiTokenManager, $validator);
        $tester = new CommandTester($command);

        $apiTokenManager->expects($this->never())->method('generateToken');
        $apiTokenManager->expects($this->never())->method('create');

        $exitCode = $tester->execute([
            '--name' => 'abc',
            '--scopes' => ['keycloak'],
        ]);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $tester->getDisplay();
        self::assertStringContainsString('Validation failed:', $output);
    }

    public function testExecuteFailureWithInvalidScope(): void
    {
        // Fresh validator mock tailored for this test
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([
            new \Symfony\Component\Validator\ConstraintViolation(
                'One or more of the given values is invalid.',
                null,
                [],
                '',
                'scopes',
                ['keycloak', 'invalid-scope', 'dovecot']
            ),
        ]));

        $apiTokenManager = $this->createMock(ApiTokenManager::class);
        $command = new ApiTokenCreateCommand($apiTokenManager, $validator);
        $tester = new CommandTester($command);

        $apiTokenManager->expects($this->never())->method('generateToken');
        $apiTokenManager->expects($this->never())->method('create');

        $exitCode = $tester->execute([
            '--name' => 'Invalid Scope Token',
            '--scopes' => ['keycloak', 'invalid-scope', 'dovecot'],
        ]);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $tester->getDisplay();
        self::assertStringContainsString('Validation failed:', $output);
    }

    public function testExecuteFailureWithApiTokenManagerException(): void
    {
        $tokenName = 'Error Token';
        $plainToken = 'generated-plain-token-error';
        $scopes = ['keycloak'];

        $apiTokenManager = $this->createMock(ApiTokenManager::class);

        $apiTokenManager
            ->expects($this->once())
            ->method('generateToken')
            ->willReturn($plainToken);

        $apiTokenManager
            ->expects($this->once())
            ->method('create')
            ->with($plainToken, $tokenName, $scopes)
            ->willThrowException(new RuntimeException('Database error'));

        $command = new ApiTokenCreateCommand($apiTokenManager, $this->validator);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--name' => $tokenName,
            '--scopes' => $scopes,
        ]);

        self::assertEquals(Command::FAILURE, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Failed to create API token: Database error', $output);
    }

    public function testCommandConfiguration(): void
    {
        self::assertEquals('app:api-token:create', $this->command->getName());
        self::assertEquals('Create a new API token with specified name and scopes', $this->command->getDescription());

        $definition = $this->command->getDefinition();

        // Arguments: none required anymore for name
        self::assertFalse($definition->hasArgument('name'));

        // Options
        self::assertTrue($definition->hasOption('name'));
        self::assertTrue($definition->hasOption('scopes'));
        self::assertFalse($definition->hasOption('all-scopes'));
        self::assertFalse($definition->hasOption('print-token')); // Option should be removed

        self::assertTrue($definition->getOption('scopes')->isArray());
    }

    private function createApiToken(int $id, string $name, array $scopes): ApiToken
    {
        $apiToken = $this->createStub(ApiToken::class);
        $apiToken->method('getId')->willReturn($id);
        $apiToken->method('getName')->willReturn($name);
        $apiToken->method('getScopes')->willReturn($scopes);
        $apiToken->method('getCreationTime')->willReturn(new DateTimeImmutable('2025-09-03 10:00:00'));

        return $apiToken;
    }
}
