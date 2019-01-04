<?php

namespace App\Tests\Command;

use App\Command\VoucherUnlinkCommand;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Handler\SuspiciousChildrenHandler;
use App\Repository\VoucherRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class VoucherUnlinkCommandTest extends TestCase
{
    /**
     * @var VoucherUnlinkCommand
     */
    private $command;

    public function setUp()
    {
        $manager = $this->getManager();
        $handler = $this->getMockBuilder(SuspiciousChildrenHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->command = new VoucherUnlinkCommand($manager, $handler);
    }

    public function testExecute()
    {
        $application = new Application();
        $application->add($this->command);

        $command = $application->find('usrmgmt:voucher:unlink');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            ['command' => $command->getName()],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertContains('unlink 2 vouchers', $output);
        $this->assertContains('Suspicious User suspicious@example.org has invited child@example.org.', $output);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function getManager()
    {
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->method('getOldVouchers')
            ->will($this->returnValue($this->getResult()));

        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        return $manager;
    }

    /**
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getResult()
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
        $voucher1->setRedeemedTime(new \DateTime('2018-06-27T09:37:20.046074+0000'));
        $voucher1->setInvitedUser($user2);

        $voucher2 = new Voucher();
        $voucher2->setId(2);
        $voucher2->setUser($user2);
        $voucher2->setCode('foobar');
        $voucher2->setRedeemedTime(new \DateTime('2018-06-27T09:37:20.046074+0000'));

        $result = [
            $voucher1,
            $voucher2,
        ];

        return $result;
    }

    public function testGetSuspiciousChildren()
    {
        $this->assertEquals($this->command->getSuspiciousChildren([]), []);

        $voucher = new Voucher();
        $this->assertEquals($this->command->getSuspiciousChildren([$voucher]), []);

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())->method('getUsername')->willReturn('child@example.org');
        $user->expects($this->atLeastOnce())->method('setInvitationVoucher');
        $voucher->setInvitedUser($user);
        $this->assertEquals($this->command->getSuspiciousChildren([$voucher]), []);

        $parent = new User();
        $parent->setEmail('suspicious@example.org');
        $parent->setRoles([Roles::SUSPICIOUS]);
        $voucher->setUser($parent);
        $this->assertEquals(
            $this->command->getSuspiciousChildren([$voucher]),
            ['child@example.org' => 'suspicious@example.org']);
    }
}
