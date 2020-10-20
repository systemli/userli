<?php

namespace App\Tests\Command;

use App\Command\MuninVoucherCommand;
use App\Repository\VoucherRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MuninVoucherCommandTest extends TestCase
{
    public function testExecute()
    {
        $repository = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())->method('count')->willReturn(10);
        $repository->expects($this->any())->method('countRedeemedVouchers')->willReturn(2);

        $manager = $this->getMockBuilder(ObjectManager::class)
            ->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        $command = new MuninVoucherCommand($manager);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('voucher_total.value 10', $output);
        self::assertStringContainsString('voucher_redeemed.value 2', $output);

        $commandTester->execute(['--autoconf' => true]);

        $output = $commandTester->getDisplay();

        self::assertEquals("yes\n", $output);

        $commandTester->execute(['--config' => true]);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('graph_title User Vouchers', $output);
        self::assertStringContainsString('graph_category Mail', $output);
        self::assertStringContainsString('graph_vlabel Voucher Counters', $output);
        self::assertStringContainsString('voucher_total.label Total Vouchers', $output);
        self::assertStringContainsString('voucher_total.min 0', $output);
        self::assertStringContainsString('voucher_redeemed.label Redeemed Vouchers', $output);
        self::assertStringContainsString('voucher_redeemed.type GAUGE', $output);
        self::assertStringContainsString('voucher_redeemed.min 0', $output);
    }
}
