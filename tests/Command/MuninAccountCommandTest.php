<?php

namespace App\Tests\Command;

use App\Command\MuninAccountCommand;
use App\Repository\OpenPgpKeyRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MuninAccountCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->method('countUsers')->willReturn(10);
        $userRepository->method('countDeletedUsers')->willReturn(3);
        $userRepository->method('countUsersWithRecoveryToken')->willReturn(5);
        $userRepository->method('countUsersWithMailCrypt')->willReturn(7);

        $openPgpKeyRepository = $this->getMockBuilder(OpenPgpKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $openPgpKeyRepository->method('countKeys')->willReturn(2);

        $manager = $this->getMockBuilder(ObjectManager::class)
            ->getMock();
        $manager->method('getRepository')->willReturnMap([
            ['App:User', $userRepository],
            ['App:OpenPgpKey', $openPgpKeyRepository],
            ]);

        $command = new MuninAccountCommand($manager);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertEquals("account.value 10\ndeleted.value 3\nrecovery_tokens.value 5\nmail_crypt_keys.value 7\nopenpgp_keys.value 2\n", $output);

        $commandTester->execute(['--autoconf' => true]);

        $output = $commandTester->getDisplay();

        self::assertEquals("yes\n", $output);

        $commandTester->execute(['--config' => true]);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('graph_title User Accounts', $output);
        self::assertStringContainsString('graph_category Mail', $output);
        self::assertStringContainsString('graph_vlabel Account Counters', $output);
        self::assertStringContainsString('account.label Active accounts', $output);
        self::assertStringContainsString('account.type GAUGE', $output);
        self::assertStringContainsString('account.min 0', $output);
        self::assertStringContainsString('deleted.label Deleted accounts', $output);
        self::assertStringContainsString('recovery_tokens.label Active accounts with recovery token', $output);
        self::assertStringContainsString('mail_crypt_keys.label Active accounts with mailbox encryption', $output);
        self::assertStringContainsString('openpgp_keys.label OpenPGP keys', $output);
    }
}
