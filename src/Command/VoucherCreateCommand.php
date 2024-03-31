<?php

namespace App\Command;

use App\Creator\VoucherCreator;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(name: 'app:voucher:create')]
class VoucherCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly RouterInterface $router,
        private readonly VoucherCreator $creator,
        private readonly string $appUrl)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create voucher for a specific user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User who gets the voucher(s) assigned')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'How many voucher to create', 3)
            ->addOption('print', 'p', InputOption::VALUE_NONE, 'Show vouchers')
            ->addOption('print-links', 'l', InputOption::VALUE_NONE, 'Show links to vouchers');
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
            $voucher = $this->creator->create($user);
            if (true === $input->getOption('print-links')) {
                $output->write(sprintf("%s\n", $this->router->generate(
                    'register_voucher',
                    ['voucher' => $voucher->getCode()]
                )));
            } elseif (true === $input->getOption('print')) {
                $output->write(sprintf("%s\n", $voucher->getCode()));
            }
        }

        return 0;
    }
}
