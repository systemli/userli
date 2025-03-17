<?php

namespace App\Tests\Command;

use App\Command\VoucherCountCommand;
use App\Entity\User;
use App\Entity\Voucher;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class VoucherCountCommandTest extends TestCase
{
    private VoucherCountCommand $command;

    public function setUp(): void
    {
        $user = new User();
        $user->setEmail('user@example.org');
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findByEmail')->willReturnMap(
            [
                ['user@example.org', $user],
                ['nonexistent@example.org', null],
            ]
        );

        $voucherRepository = $this->createMock(VoucherRepository::class);
        $voucherRepository->method('countVouchersByUser')
            ->willReturnOnConsecutiveCalls(2, 5);

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
        $application->add($this->command);

        $command = $application->find('app:voucher:count');
        $commandTester = new CommandTester($command);

        $this->expectException(UserNotFoundException::class);
        $commandTester->execute([
            '--user' => 'nonexistent@example.org',
        ]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('', $output);
    }

    public function testExecuteWithUser(): void
    {
        $application = new Application();
        $application->add($this->command);

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
