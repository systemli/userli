<?php

namespace App\Tests\Command;

use App\Command\MuninAccountCommand;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MuninAccountCommandTest extends TestCase
{
    public function testExecute()
    {
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->method('countUsers')->willReturn(10);
        $repository->method('countDeletedUsers')->willReturn(3);
        $repository->method('countUsersWithRecoveryToken')->willReturn(5);
        $repository->method('countUsersWithMailCrypt')->willReturn(7);
        $repository->method('countUsersWithWkdKey')->willReturn(2);

        $manager = $this->getMockBuilder(ObjectManager::class)
            ->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        $command = new MuninAccountCommand($manager);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertEquals("account.value 10\ndeleted.value 3\nrecovery_tokens.value 5\nmail_crypt_keys.value 7\nwkd_keys.value 2\n", $output);

        $commandTester->execute(['--autoconf' => true]);

        $output = $commandTester->getDisplay();

        self::assertEquals("yes\n", $output);

        $commandTester->execute(['--config' => true]);

        $output = $commandTester->getDisplay();

        self::assertContains('graph_title User Accounts', $output);
        self::assertContains('graph_category Mail', $output);
        self::assertContains('graph_vlabel Account Counters', $output);
        self::assertContains('account.label Active accounts', $output);
        self::assertContains('account.type GAUGE', $output);
        self::assertContains('account.min 0', $output);
        self::assertContains('deleted.label Deleted accounts', $output);
        self::assertContains('recovery_tokens.label Active accounts with recovery token', $output);
        self::assertContains('mail_crypt_keys.label Active accounts with mailbox encryption', $output);
        self::assertContains('wkd_keys.label Active accounts with WKD key', $output);
    }
}
