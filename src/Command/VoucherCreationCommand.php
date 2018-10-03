<?php

namespace App\Command;

use App\Creator\VoucherCreator;
use App\Factory\VoucherFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @author louis <louis@systemli.org>
 */
class VoucherCreationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('usrmgmt:voucher:create')
            ->setDescription('Create voucher for a specific user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User who get the voucher(s)')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Count of the voucher which will created', 3);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getOption('user');
        $manager = $this->getContainer()->get('doctrine')->getManager();

        if (empty($email) || null === $user = $manager->getRepository('App:User')->findByEmail($email)) {
            throw new UsernameNotFoundException(sprintf('User with email %s not found!', $email));
        }

        $manager = $this->getContainer()->get('doctrine')->getManager();

        for ($i = 1; $i <= $input->getOption('count'); ++$i) {
            $voucher = VoucherFactory::create($user);

            $manager->persist($voucher);
        }

        $manager->flush();
    }
}
