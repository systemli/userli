<?php

namespace App\Tests\Command;

use PHPUnit\Framework\MockObject\MockObject;
use Exception;
use DateTime;
use App\Command\VoucherUnlinkCommand;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class VoucherUnlinkCommandTest extends TestCase
{
    private VoucherUnlinkCommand $command;

    public function setUp(): void
    {
        $manager = $this->getManager();
        $this->command = new VoucherUnlinkCommand($manager);
    }

    public function testExecute(): void
    {
        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:voucher:unlink');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('unlink 2 vouchers', $output);
    }

    /**
     * @return MockObject
     */
    public function getManager(): EntityManagerInterface
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->method('getOldVouchers')
            ->willReturn($this->getOldVouchers());

        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }

    /**
     * @throws Exception
     */
    public function getOldVouchers(): array
    {
        $user1 = new User();
        $user1->setEmail('suspicious@example.org');
        $user1->setRoles([Roles::SUSPICIOUS]);

        $user2 = new User();
        $user2->setEmail('child@example.org');

        $voucher1 = new Voucher();
        $voucher1->setId(1);
        $voucher1->setUser($user1);
        $voucher1->setCode('blabla');
        $voucher1->setRedeemedTime(new DateTime('2018-06-27T09:37:20.046074+0000'));
        $voucher1->setInvitedUser($user2);

        $voucher2 = new Voucher();
        $voucher2->setId(2);
        $voucher2->setUser($user2);
        $voucher2->setCode('foobar');
        $voucher2->setRedeemedTime(new DateTime('2018-06-27T09:37:20.046074+0000'));

        return [
            $voucher1,
            $voucher2,
        ];
    }
}
