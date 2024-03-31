<?php

namespace App\Tests\Command;

use App\Command\VoucherCreateCommand;
use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Entity\Voucher;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class VoucherCreateCommandTest extends TestCase
{
    private VoucherCreateCommand $command;
    private UserRepository $repository;
    private RouterInterface $router;
    private VoucherCreator $creator;
    private string $baseUrl = 'https://users.example.org/register';
    private string $voucherCode = 'code';

    public function setUp(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(UserRepository::class);
        $manager->method('getRepository')->willReturn($this->repository);

        $this->router = $this->createMock(RouterInterface::class);

        $this->creator = $this->createMock(VoucherCreator::class);

        $this->command = new VoucherCreateCommand($manager, $this->router, $this->creator, $this->baseUrl);
    }

    public function testExecuteWithUnknownUser(): void
    {
        $this->repository->method('findByEmail')
            ->willReturn(null);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:voucher:create');
        $commandTester = new CommandTester($command);

        $this->expectException(UserNotFoundException::class);
        $commandTester->execute([
            '--user' => 'user@example.org',
            '--count' => 1,
            '--print'
        ]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('', $output);
    }

    public function testExecuteWithUser(): void
    {
        $user = new User();
        $user->setEmail('user@example.org');
        $this->repository->method('findByEmail')
            ->willReturn($user);

        $this->router->method('generate')
            ->willReturn($this->baseUrl . '/' . $this->voucherCode);

        $voucher = new Voucher();
        $voucher->setCode($this->voucherCode);
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
        $this->assertStringContainsString($this->voucherCode, $output);

        // Test show links to vouchers

        $commandTester->execute([
            '--user' => $user->getEmail(),
            '--count' => 1,
            '--print-links' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString($this->baseUrl . '/' . $this->voucherCode, $output);
    }

    public function testExecuteWithSuspiciousUser(): void
    {
        $user = new User();
        $user->setEmail('suspicious@example.org');
        $this->repository->method('findByEmail')
            ->willReturn($user);

        $voucher = new Voucher();
        $voucher->setCode($this->voucherCode);
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
}
