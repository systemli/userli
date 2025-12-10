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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class VoucherCreateCommandTest extends TestCase
{
    private VoucherCreateCommand $command;
    private MockObject $repository;
    private MockObject $router;
    private MockObject $creator;
    private MockObject $settingsService;
    private MockObject $requestContext;

    protected function setUp(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(UserRepository::class);
        $manager->method('getRepository')->willReturn($this->repository);

        $this->router = $this->createMock(RouterInterface::class);
        $this->creator = $this->createMock(VoucherCreator::class);
        $this->settingsService = $this->createMock(SettingsService::class);
        $this->requestContext = $this->createMock(RequestContext::class);

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

        // Settings service should not be called when user doesn't exist
        // because UserNotFoundException is thrown before settings are accessed
        $this->settingsService->expects(self::never())
            ->method('get');

        $this->requestContext->expects(self::never())
            ->method('setBaseUrl');

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:voucher:create');
        $commandTester = new CommandTester($command);

        $this->expectException(UserNotFoundException::class);
        $commandTester->execute([
            '--user' => 'user@example.org',
            '--count' => 1,
            '--print',
        ]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('', $output);
    }

    public function testExecuteWithUser(): void
    {
        $baseUrl = 'https://users.example.org';
        $voucherCode = 'code';

        $user = new User('user@example.org');
        $this->repository->method('findByEmail')
            ->willReturn($user);

        // Settings service should always return valid app_url
        $this->settingsService->expects(self::atLeastOnce())
            ->method('get')
            ->with('app_url')
            ->willReturn($baseUrl);

        $this->requestContext->expects(self::atLeastOnce())
            ->method('setBaseUrl')
            ->with($baseUrl);

        $this->router->method('generate')
            ->willReturn($baseUrl.'/register/'.$voucherCode);

        $voucher = new Voucher();
        $voucher->setCode($voucherCode);
        $this->creator->method('create')
            ->willReturn($voucher);

        $application = new Application();
        $application->add($this->command);

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

        // Settings service should always return valid app_url
        $this->settingsService->expects(self::once())
            ->method('get')
            ->with('app_url')
            ->willReturn($baseUrl);

        $this->requestContext->expects(self::once())
            ->method('setBaseUrl')
            ->with($baseUrl);

        $voucher = new Voucher();
        $voucher->setCode($voucherCode);
        $exception = $this->createMock(ValidationException::class);
        $this->creator->method('create')
            ->willThrowException($exception);

        $application = new Application();
        $application->add($this->command);

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

        $this->repository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->settingsService->expects(self::once())
            ->method('get')
            ->with('app_url')
            ->willReturn($baseUrl);

        $this->requestContext->expects(self::once())
            ->method('setBaseUrl')
            ->with($baseUrl);

        $voucher = new Voucher();
        $voucher->setCode($voucherCode);

        $this->creator->expects(self::once())
            ->method('create')
            ->with($user)
            ->willReturn($voucher);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:voucher:create');
        $commandTester = new CommandTester($command);

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

        $this->repository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->settingsService->expects(self::once())
            ->method('get')
            ->with('app_url')
            ->willReturn($baseUrl);

        $this->requestContext->expects(self::once())
            ->method('setBaseUrl')
            ->with($baseUrl);

        $voucher = new Voucher();
        $voucher->setCode($voucherCode);

        $this->creator->expects(self::once())
            ->method('create')
            ->with($user)
            ->willReturn($voucher);

        $this->router->expects(self::once())
            ->method('generate')
            ->with('register_voucher', ['voucher' => $voucherCode])
            ->willReturn($baseUrl.'/register/'.$voucherCode);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:voucher:create');
        $commandTester = new CommandTester($command);

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
