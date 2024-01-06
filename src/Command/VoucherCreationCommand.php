<?php

namespace App\Command;

use App\Entity\User;
use App\Factory\VoucherFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class VoucherCreationCommand extends Command
{
    public function __construct(private EntityManagerInterface $manager, private RouterInterface $router, private string $appUrl)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:voucher:create')
            ->setDescription('Create voucher for a specific user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User who get the voucher(s)')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Count of the voucher which will created', 3)
            ->addOption('print', 'p', InputOption::VALUE_NONE, 'Print out vouchers')
            ->addOption('print-links', 'l', InputOption::VALUE_NONE, 'Print out links to vouchers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getOption('user');

        // Set
        $context = $this->router->getContext();
        $context->setBaseUrl($this->appUrl);

        if (empty($email) || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            throw new UserNotFoundException(sprintf('User with email %s not found!', $email));
        }

        for ($i = 1; $i <= $input->getOption('count'); ++$i) {
            $voucher = VoucherFactory::create($user);
            if (true === $input->getOption('print-links')) {
                $output->write(sprintf("%s\n", $this->router->generate(
                    'register_voucher',
                    ['_locale' => 'en', 'voucher' => $voucher->getCode()]
                )));
            } elseif (true === $input->getOption('print')) {
                $output->write(sprintf("%s\n", $voucher->getCode()));
            }

            $this->manager->persist($voucher);
        }

        $this->manager->flush();

        return 0;
    }
}
