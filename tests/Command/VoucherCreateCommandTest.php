<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\VoucherCreateCommand;
use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Entity\Voucher;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class VoucherCreateCommandTest extends TestCase
{
    private VoucherCreateCommand $command;
    private Stub&UserRepository $repository;
    private Stub&RouterInterface $router;
    private Stub&VoucherCreator $creator;
    private Stub&SettingsService $settingsService;
    private Stub&RequestContext $requestContext;

    protected function setUp(): void
    {
        $manager = $this->createStub(EntityManagerInterface::class);
        $this->repository = $this->createStub(UserRepository::class);
        $manager->method('getRepository')->willReturn($this->repository);

        $this->router = $this->createStub(RouterInterface::class);
        $this->creator = $this->createStub(VoucherCreator::class);
        $this->settingsService = $this->createStub(SettingsService::class);
        $this->requestContext = $this->createStub(RequestContext::class);

        // Setup router context
        $this->router->method('getContext')->willReturn($this->requestContext);

        $this->command = new VoucherCreateCommand(
            $manager,
            $this->router,
            $this->creator,
            $this->settingsService
        );
    }

    public function testExecuteWithUnknownUser(): void
    {
        $this->repository->method('findByEmail')
            ->willReturn(null);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:voucher:create');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--user' => 'user@example.org',
            '--count' => 1,
            '--print',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('User with email', $commandTester->getDisplay());
    }

    public function testExecuteWithUser(): void
    {
        $baseUrl = 'https://users.example.org';
        $voucherCode = 'code';

        $user = new User('user@example.org');
        $this->repository->method('findByEmail')
            ->willReturn($user);

        $this->settingsService
            ->method('get')
            ->with('app_url')
            ->willReturn($baseUrl);

        $this->router->method('generate')
            ->willReturn($baseUrl.'/register/'.$voucherCode);

        $voucher = new Voucher($voucherCode);
        $this->creator->method('create')
            ->willReturn($voucher);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:voucher:create');
        $commandTester = new CommandTester($command);

        // Test show vouchers

        $commandTester->execute([
            '--user' => $user->getEmail(),
            '--count' => 1,
            '--print' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString($voucherCode, $output);

        // Test show links to vouchers

        $commandTester->execute([
            '--user' => $user->getEmail(),
            '--count' => 1,
            '--print-links' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString($baseUrl.'/register/'.$voucherCode, $output);
    }

    public function testExecuteWithSuspiciousUser(): void
    {
        $baseUrl = 'https://users.example.org';
        $voucherCode = 'code';

        $user = new User('suspicious@example.org');
        $this->repository->method('findByEmail')
            ->willReturn($user);

        $this->settingsService
            ->method('get')
            ->with('app_url')
            ->willReturn($baseUrl);

        $voucher = new Voucher($voucherCode);
        $exception = $this->createStub(ValidationException::class);
        $this->creator->method('create')
            ->willThrowException($exception);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:voucher:create');
        $commandTester = new CommandTester($command);

        $this->expectException(ValidationException::class);
        $commandTester->execute([
            '--user' => $user->getEmail(),
            '--count' => 1,
        ]);
    }

    public function testExecuteWithPrintOption(): void
    {
        $email = 'user@example.org';
        $baseUrl = 'https://users.example.org';
        $voucherCode = 'abc123';

        $user = new User($email);

        $manager = $this->createStub(EntityManagerInterface::class);
        $repository = $this->createMock(UserRepository::class);
        $manager->method('getRepository')->willReturn($repository);
        $settingsService = $this->createMock(SettingsService::class);
        $creator = $this->createMock(VoucherCreator::class);
        $requestContext = $this->createMock(RequestContext::class);
        $router = $this->createStub(RouterInterface::class);
        $router->method('getContext')->willReturn($requestContext);

        $repository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $settingsService->expects(self::once())
            ->method('get')
            ->with('app_url')
            ->willReturn($baseUrl);

        $requestContext->expects(self::once())
            ->method('setBaseUrl')
            ->with($baseUrl);

        $voucher = new Voucher($voucherCode);

        $creator->expects(self::once())
            ->method('create')
            ->with($user)
            ->willReturn($voucher);

        $command = new VoucherCreateCommand($manager, $router, $creator, $settingsService);

        $application = new Application();
        $application->addCommand($command);

        $consoleCommand = $application->find('app:voucher:create');
        $commandTester = new CommandTester($consoleCommand);

        $commandTester->execute([
            '--user' => $email,
            '--count' => 1,
            '--print' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertMatchesRegularExpression('/^[a-z_\-0-9]{6}$/i', trim($output));
        self::assertStringContainsString($voucherCode, $output);
    }

    public function testExecuteWithPrintLinksOption(): void
    {
        $email = 'user@example.org';
        $baseUrl = 'https://users.example.org';
        $voucherCode = 'xyz789';

        $user = new User($email);

        $manager = $this->createStub(EntityManagerInterface::class);
        $repository = $this->createMock(UserRepository::class);
        $manager->method('getRepository')->willReturn($repository);
        $settingsService = $this->createMock(SettingsService::class);
        $creator = $this->createMock(VoucherCreator::class);
        $requestContext = $this->createMock(RequestContext::class);
        $router = $this->createMock(RouterInterface::class);
        $router->method('getContext')->willReturn($requestContext);

        $repository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $settingsService->expects(self::once())
            ->method('get')
            ->with('app_url')
            ->willReturn($baseUrl);

        $requestContext->expects(self::once())
            ->method('setBaseUrl')
            ->with($baseUrl);

        $voucher = new Voucher($voucherCode);

        $creator->expects(self::once())
            ->method('create')
            ->with($user)
            ->willReturn($voucher);

        $router->expects(self::once())
            ->method('generate')
            ->with('register_voucher', ['voucher' => $voucherCode])
            ->willReturn($baseUrl.'/register/'.$voucherCode);

        $command = new VoucherCreateCommand($manager, $router, $creator, $settingsService);

        $application = new Application();
        $application->addCommand($command);

        $consoleCommand = $application->find('app:voucher:create');
        $commandTester = new CommandTester($consoleCommand);

        $commandTester->execute([
            '--user' => $email,
            '--count' => 1,
            '--print-links' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertMatchesRegularExpression('|^https://users\.example\.org/register/[a-z_\-0-9]{6}$|i', trim($output));
        self::assertStringContainsString($baseUrl.'/register/'.$voucherCode, $output);
    }
}
