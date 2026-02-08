<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\VoucherCountCommand;
use App\Entity\User;
use App\Entity\Voucher;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class VoucherCountCommandTest extends TestCase
{
    private VoucherCountCommand $command;

    protected function setUp(): void
    {
        $user = new User('user@example.org');
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findByEmail')->willReturnMap(
            [
                ['user@example.org', $user],
                ['nonexistent@example.org', null],
            ]
        );

        $voucherCallCount = 0;
        $voucherRepository = $this->createMock(VoucherRepository::class);
        $voucherRepository->method('countVouchersByUser')
            ->willReturnCallback(static function () use (&$voucherCallCount) {
                return [2, 5][$voucherCallCount++];
            });

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [User::class, $userRepository],
            [Voucher::class, $voucherRepository],
        ]);

        $this->command = new VoucherCountCommand($manager);
    }

    public function testExecuteWithUnknownUser(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:voucher:count');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--user' => 'nonexistent@example.org',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('User with email', $commandTester->getDisplay());
    }

    public function testExecuteWithUser(): void
    {
        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:voucher:count');
        $commandTester = new CommandTester($command);

        // Test counting unredeemed vouchers
        $commandTester->execute([
            '--user' => 'user@example.org',
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Voucher count for user user@example.org', $output);
        $this->assertStringContainsString('Used: 2', $output);
        $this->assertStringContainsString('Unused: 5', $output);
    }
}
