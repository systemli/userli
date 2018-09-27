<?php

namespace App\Tests\Command;

use App\Command\VoucherUnlinkCommand;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Handler\SuspiciousChildrenHandler;
use App\Repository\VoucherRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class VoucherUnlinkCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $manager = $this->getManager();
        $handler = $this->getMockBuilder(SuspiciousChildrenHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $application->add(new VoucherUnlinkCommand($manager, $handler));

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

    public function getManager()
    {
        $repository = $this->getMockBuilder(VoucherRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->method('createQueryBuilder')
            ->with('voucher')
            ->will($this->returnValue($queryBuilder));

        // We use QueryBuilder as Fluent Interface
        // several times, so we need to make it sequence
        $queryBuilder->expects($this->at(0))
            ->method('join')
            ->will($this->returnValue($queryBuilder));
        $queryBuilder->expects($this->at(1))
            ->method('where')
            ->will($this->returnValue($queryBuilder));
        $queryBuilder->expects($this->at(2))
            ->method('setParameter')
            ->will($this->returnValue($queryBuilder));
        $queryBuilder->expects($this->at(3))
            ->method('orderBy')
            ->will($this->returnValue($queryBuilder));

        // We use AbstractQuery
        $getQuery = $this->getMockBuilder(AbstractQuery::class)
            ->setMethods(array('getResult'))
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $queryBuilder->expects($this->at(4))
            ->method('getQuery')
            ->will($this->returnValue($getQuery));

        $getQuery->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($this->getResult()));

        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        return $manager;
    }

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
}
